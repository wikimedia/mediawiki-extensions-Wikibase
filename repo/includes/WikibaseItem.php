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

	const TYPE_TEXT = 'text';
	const TYPE_SCALAR = 'scalar'; # unit, precision, point-in-time
	const TYPE_DATE = 'date';
	const TYPE_TERM = 'term'; # lang, pronunciation
	const TYPE_ENTITY_REF = 'ref';

	const PROP_LABEL = 'label';
	const PROP_DESCRIPTION = 'description';
	const PROP_ALIAS = 'alias';

	protected $data;

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 */
	protected function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return WikibaseItem
	 */
	public static function newFromArray( array $data ) {
		return new static( $data );
	}

	/**
	 * Returns the id of the item or null if it is not in the datastore yet.
	 *
	 * @since 0.1
	 *
	 * @return integer|null
	 */
	public function getId() {
		// TODO
	}

	/**
	 * @param integer $id
	 */
	protected function setId( $id ) {

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
		$success = $this->insetIfNeeded( $articleId );

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

		if ( is_null( $this->item->getId() ) ) {
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

		// TODO
		foreach ( array() as $siteId => $pageName ) {
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

	// TODO
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
	public function addSiteLink( $siteId, $pageName, $update = false ) {
		// TODO: update blob

		$dbw = wfGetDB( DB_MASTER );

		if ( $update ) {
			$this->removeSiteLink( $siteId, $pageName );
		}
		else {
			$exists = $dbw->selectRow(
				'wb_items_per_site',
				array( 'ips_item_id' ),
				$this->getSiteLinkConds( $siteId, $pageName ),
				__METHOD__
			) !== false;

			if ( $exists ) {
				return false;
			}
		}

		return $dbw->insert(
			'wb_items_per_site',
			$this->getSiteLinkConds( $siteId, $pageName ),
			__METHOD__
		);
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

		$dbw = wfGetDB( DB_MASTER );

		return $dbw->delete(
			'wb_items_per_site',
			$this->getSiteLinkConds( $siteId, $pageName ),
			__METHOD__
		);
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
	 * @param Language $lang
	 * @return String|null description
	 */
	public function getDescription( Language $lang ) {
		$data = $this->data;
		if ( !isset( $data['description'][$lang->getCode()] ) ) {
			return null;
		} else {
			return $data['description'][$lang->getCode()]['value'];
		}
	}

	/**
	 * @param Language $lang
	 * @return String|null label
	 */
	public function getLabel( Language $lang ) {
		$data = $this->data;
		if ( !isset( $data['label'][$lang->getCode()] ) ) {
			return null;
		} else {
			return $data['label'][$lang->getCode()]['value'];
		}
	}

	/**
	 * @param Language $lang
	 * @return array titles (languageCode => value)
	 */
	public function getTitles( Language $lang ) {
		$data = $this->data;
		$titles = array();
		foreach ( $data['titles'] as $langCode => $title ) {
			$titles[$langCode] = $title['value'];
		}
		return $titles;
	}

}
