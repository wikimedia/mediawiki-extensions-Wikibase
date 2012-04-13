<?php

/**
 * Represents a single Wikibase item.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
 *
 * @since 0.1
 *
 * @file WikibaseItem.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseItem {

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * Id of the item (the 42 in q42 used as page name and in exports).
	 * Integer when set. False when not initialized. Null when the item is new and unsaved.
	 *
	 * @since 0.1
	 * @var integer|false|null
	 */
	protected $id = false;

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 */
	protected function __construct( array $data, $clean = false ) {
		$this->data = $data;

		if ( $clean ) {
			// TODO: should be moved out of constructor
			$this->cleanStructure();
		}
	}

	/**
	 * Cleans the internal array structure.
	 * This consists of adding elements the code expects to be present later on
	 * and migrating or removing elements after changes to the structure are made.
	 * Should typically be called before using any of the other methods.
	 *
	 * @since 0.1
	 */
	public function cleanStructure() {
		foreach ( array( 'links', 'label', 'description' ) as $field ) {
			if ( !array_key_exists( $field, $this->data ) ) {
				$this->data[$field] = array();
			}
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return WikibaseItem
	 */
	public static function newFromArray( array $data ) {
		return new static( $data, true );
	}

	/**
	 * Get an array representing the WikibaseItem as they are
	 * stored in the article table and can be passed to newFromArray.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function toArray() {
		$data = $this->data;

		if ( is_null( $this->getId() ) ) {
			if ( array_key_exists( 'entity', $data ) ) {
				unset( $data['entity'] );
			}
		}
		else {
			$data['entity'] = 'q' . $this->getId();
		}

		return $data;
	}

	/**
	 * Returns the id of the item or null if it is not in the datastore yet.
	 *
	 * @since 0.1
	 *
	 * @return integer|null
	 */
	public function getId() {
		if ( $this->id === false ) {
			$this->id = array_key_exists( 'entity', $this->data ) ? substr( $this->data['entity'], 1 ) : null;
		}

		return $this->id;
	}

	/**
	 * @param integer $id
	 */
	protected function setId( $id ) {
		$this->id = $id;
	}

	/**
	 * Saves the item in a structured fashion, including both relational and denormalized storage.
	 * Basically does all the storage except the blob in the page table.
	 *
	 * @since 0.1
	 *
	 * @param integer $articleId
	 *
	 * @return boolean Success indicator
	 */
	public function structuredSave( $articleId ) {
		$success = $this->save( $articleId );

		if ( $success ) {
			$dbw = wfGetDB( DB_MASTER );

			$dbw->begin();
			$this->saveSiteLinks();
			$this->saveMultilangFields();
			$dbw->commit();
		}

		return $success;
	}

	/**
	 * Saves the primary fields in the wb_items table.
	 * If the item does not exist yet (ie the id is null), it will be inserted, and the id will be set.
	 *
	 * @since 0.1
	 *
	 * @param integer $articleId
	 *
	 * @return boolean Success indicator
	 */
	protected function save( $articleId ) {
		$dbw = wfGetDB( DB_MASTER );

		$fields = array();

		$success = true;

		if ( is_null( $this->getId() ) ) {
			$fields['item_page_id'] = $articleId;

			$success = $dbw->insert(
				'wb_items',
				$fields,
				__METHOD__
			);

			if ( $success ) {
				$this->setId( $dbw->insertId() );
			}
		}
		elseif ( !empty( $fields ) ) {
			$success = $dbw->update(
				'wb_items',
				$fields,
				array( 'item_page_id' => $this->getId() ),
				__METHOD__
			);
		}

		return $success;
	}

	/**
	 * Saves the links to other sites (for example which article on which Wikipedia corresponds to this item).
	 * This info is saved in wb_items_per_site.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	protected function saveSiteLinks() {
		$dbw = wfGetDB( DB_MASTER );

		$idField = array( 'ips_item_id' => $this->getId() );

		$success = $dbw->delete(
			'wb_items_per_site',
			$idField,
			__METHOD__
		);

		foreach ( $this->getSiteLinks() as $siteId => $pageName ) {
			$success = $dbw->insert(
				'wb_items_per_site',
				array_merge(
					$idField,
					array(
						'ips_site_id' => $siteId,
						'ips_site_page' => $pageName,
					)
				),
				__METHOD__
			) && $success;
		}

		return $success;
	}

	/**
	 * Saves the fields that have per-language values, such as the labels and descriptions.
	 * This info is saved in wb_texts_per_lang.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	protected function saveMultilangFields() {
		$dbw = wfGetDB( DB_MASTER );

		$idField = array( 'tpl_item_id' => $this->getId() );

		$success = $dbw->delete(
			'wb_texts_per_lang',
			$idField,
			__METHOD__
		);

		// TODO
		foreach ( array() as $siteId => $pageName ) {
			$success = $dbw->insert(
				'wb_texts_per_lang',
				array_merge(
					$idField,
					array(
						'ips_site_id' => $siteId,
						'ips_site_page' => $pageName,
					)
				),
				__METHOD__
			) && $success;
		}

		return $success;
	}

	/**
	 * Get the item id for a site and page pair.
	 * Returns false when there is no such pair.
	 *
	 * @since 0.1
	 *
	 * @param integer $siteId
	 * @param string $pageName
	 *
	 * @return false|integer
	 */
	public static function getIdForSiteLink( $siteId, $pageName ) {
		$dbr = wfGetDB( DB_SLAVE );

		$result = $dbr->selectRow(
			'wb_items_per_site',
			array( 'ips_item_id' ),
			array(
				'ips_site_id' => $siteId,
				'ips_site_page' => $pageName,
			),
			__METHOD__
		);

		return $result === false ? $result : $result->ips_item_id;
	}

	/**
	 * Adds a site link.
	 *
	 * @since 0.1
	 *
	 * @param integer $siteId
	 * @param string $pageName
	 * @param boolean $update If a link to this site already exists, update it?
	 *
	 * @return boolean Success indicator
	 */
	public function addSiteLink( $siteId, $pageName, $update = true ) {
		if ( !array_key_exists( 'titles', $this->data ) ) {
			$this->data['titles'] = array();
		}

		if ( !array_key_exists( $siteId, $this->data['titles'] ) ) {
			$this->data['titles'][$siteId] = array();
		}

		$success = $update || !array_key_exists( $siteId, $this->data['titles'][$siteId] );

		if ( $success ) {
			$this->data['titles'][$siteId][$siteId] = array(
				'site' => $siteId,
				'title' => $pageName
			);
		}

		return $success;

//		$dbw = wfGetDB( DB_MASTER );
//
//		if ( $update ) {
//			$this->removeSiteLink( $siteId, $pageName );
//		}
//		else {
//			$exists = $dbw->selectRow(
//				'wb_items_per_site',
//				array( 'ips_item_id' ),
//				$this->getSiteLinkConds( $siteId, $pageName ),
//				__METHOD__
//			) !== false;
//
//			if ( $exists ) {
//				return false;
//			}
//		}
//
//		return $dbw->insert(
//			'wb_items_per_site',
//			$this->getSiteLinkConds( $siteId, $pageName ),
//			__METHOD__
//		);
	}

	/**
	 * Returns the conditions needed to find the link to an external page
	 * for this item.
	 *
	 * @since 0.1
	 *
	 * @param integer $siteId
	 * @param string $pageName
	 *
	 * @return array
	 */
	protected function getSiteLinkConds( $siteId, $pageName ) {
		return array(
			'ips_item_id' => $this->getId(),
			'ips_site_id' => $siteId,
			'ips_site_page' => $pageName,
		);
	}

	/**
	 * Removes a site link.
	 *
	 * @since 0.1
	 *
	 * @param integer $siteId
	 * @param string $pageName
	 *
	 * @return boolean Success indicator
	 */
	public function removeSiteLink( $siteId, $pageName ) {
		// TODO: update blob

//		$dbw = wfGetDB( DB_MASTER );
//
//		return $dbw->delete(
//			'wb_items_per_site',
//			$this->getSiteLinkConds( $siteId, $pageName ),
//			__METHOD__
//		);
	}

	public function getPropertyNames() {
		//TODO: implement
	}

	public function getSystemPropertyNames() {
		//TODO: implement
	}

	public function getEditorialPropertyNames() {
		//TODO: implement
	}

	public function getStatementPropertyNames() {
		//TODO: implement
	}

	public function getPropertyMultilang( $name, $languages = null ) {
		//TODO: implement
	}

	public function getProperty( $name, $lang = null ) {
		//TODO: implement
	}

	public function getPropertyType( $name ) {
		//TODO: implement
	}

	public function isStatementProperty( $name ) {
		//TODO: implement
	}

	/**
	 * Returns the description of the item in the language with the provided code,
	 * or false in cases there is none in this language.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 *
	 * @return string|false
	 */
	public function getDescription( $langCode ) {
		return array_key_exists( $langCode, $this->data['description'] )
			? $this->data['description'][$langCode]['value'] : false;
	}

	/**
	 * Returns the label of the item in the language with the provided code,
	 * or false in cases there is none in this language.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 *
	 * @return string|false
	 */
	public function getLabel( $langCode ) {
		return array_key_exists( $langCode, $this->data['label'] )
			? $this->data['label'][$langCode]['value'] : false;
	}

	/**
	 * Returns the site links in an associative array with the following format:
	 * site id (str) => page title (str)
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getSiteLinks() {
		$titles = array();

		foreach ( $this->data['links'] as $siteId => $linkData ) {
			$titles[$siteId] = $linkData['title'];
		}

		return $titles;
	}

}
