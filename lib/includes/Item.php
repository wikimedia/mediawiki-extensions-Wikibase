<?php

namespace Wikibase;
use User, Title, WikiPage, Content, RequestContext;

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
class Item extends Entity {

	/**
	 * @since 0.1
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
	 * @var WikiPage|false
	 */
	protected $wikiPage = false;

	/**
	 * Constructor.
	 * Do not use to construct new stuff from outside of this class, use the static newFoobar methods.
	 * In other words: treat as protected (which it was, but now cannot be since we derive from Content).
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 */
	public function __construct( array $data ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );

		$this->data = $data;
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
		foreach ( array( 'links', 'label', 'description', 'aliases' ) as $field ) {
			if ( !array_key_exists( $field, $this->data ) ) {
				$this->data[$field] = array();
			}
		}

		//TODO: move legacy cleanup from getSiteLinks and getMultilangText here
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
			$this->id = array_key_exists( 'entity', $this->data ) ? (int)substr( $this->data['entity'], 1 ) : null;
		}

		return $this->id;
	}

	/**
	 * Saves the primary fields in the wb_items table.
	 * If the item does not exist yet (ie the id is null), it will be inserted, and the id will be set.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 * @todo: page based operations must be factored out of this class; they are only meaningful in the repo.
	 */
	protected function relationalSave() {
		$dbw = wfGetDB( DB_MASTER );

		$fields = array();

		$success = true;

		if ( $this->isNew() ) {
			$fields['item_id'] = null; // This is needed to have at least one field.

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
				array( 'item_id' => $this->getId() ),
				__METHOD__
			);
		}

		return $success;
	}

	/**
	 *
	 * @param WikiPage $page
	 * @param int      $flags
	 * @param int      $baseRevId
	 * @param User     $user
	 *
	 * @return \Status
	 * @see Content::prepareSave()
	 */
	public function prepareSave( WikiPage $page, $flags, $baseRevId, User $user ) {
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );

		if ( $status->isOK() ) {
			$this->checkSiteLinksForInsert( $status );
		}

		return $status;
	}

	protected function checkSiteLinksForInsert( \Status $status ) {
		$dbw = wfGetDB( DB_SLAVE );

		foreach ( $this->getSiteLinks() as $siteId => $pageName ) {
			$res = $dbw->select(
				'wb_items_per_site',
				array( 'ips_item_id' ),
				array(
					'ips_site_id' => $siteId,
					'ips_site_page' => $pageName,
				),
				__METHOD__
			);

			while ( $row = $res->fetchObject() ) {
				if ( $row->ips_item_id != $this->getId() ) {
					$title = Title::newFromID( $row->ips_item_id );

					$status->setResult( false );
					$status->error( 'wikibase-error-sitelink-already-used', $siteId, $pageName, $title->getPrefixedDBkey() );
				}
			}
		}

		return $status->isOK();
	}

	/**
	 * Saves the item.
	 * If the item does not exist yet, it will be created (ie an ID will be fetched and a new page in the data NS created).
	 *
	 * @since 0.1
	 *
	 * @param string $summary
	 * @param null|User $user
	 *
	 * @return \Status Success indicator
	 * @todo: page based operations must be factored out of this class; they are only meaningful in the repo.
	 */
	public function save( $summary = '', User $user = null ) {
		$success = $this->relationalSave();

		if ( !$success ) {
			$status = \Status::newFatal( "wikibase-error-relational-save-failed" );
		} else {
			$status = $this->getWikiPage()->doEditContent(
				$this,
				$summary,
				EDIT_AUTOSUMMARY,
				false,
				$user
			);
		}

		return $status;
	}

	/**
	 * Load the item data from the database, overriding the data currently set.
	 *
	 * @since 0.1
	 *
	 * @throws \MWException
	 * @todo: page based operations must be factored out of this class; they are only meaningful in the repo.
	 */
	public function reload() {
		if ( !$this->isNew() ) {
			$item = self::getFromId( $this->getId() );

			if ( is_null( $item ) ) {
				throw new \MWException( 'Attempt to reload item failed because it could not be obtained from the db.' );
			}
			else {
				$this->data = $item->toArray();
			}
		}
	}

	/**
	 * Removes the item.
	 *
	 * @since 0.1
	 *
	 * @param string $summary
	 * @param null|User $user
	 *
	 * @return boolean Success indicator
	 * @todo: page based operations must be factored out of this class; they are only meaningful in the repo.
	 */
	public function remove( $summary = '', User $user = null ) {
		// TODO
		return true;
	}

	/**
	 * Sets the ID.
	 * Should only be set to something determined by the store and not by the user (to avoid duplicate IDs).
	 *
	 * @since 0.1
	 *
	 * @param integer $id
	 */
	protected function setId( $id ) {
		$this->id = $id;
	}

	/**
	 * Returns if the item has an ID set or not.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isNew() {
		return is_null( $this->getId() );
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
	 * Get the ids of the items corresponding to the provided language and label pair.
	 * A description can also be provided, in which case only the id of the item with
	 * that description will be returned (as only element in the array).
	 *
	 * @since 0.1
	 *
	 * @param string $language
	 * @param string $label
	 * @param string|null $description
	 *
	 * @return array of integer
	 */
	public static function getIdsForLabel( $language, $label, $description = null ) {
		$dbr = wfGetDB( DB_SLAVE );

		$conds = array(
			'tpl_language' => $language,
			'tpl_label' => $label
		);

		if ( !is_null( $description ) ) {
			$conds['tpl_description'] = $description;
		}

		$items = $dbr->select(
			'wb_texts_per_lang',
			array( 'tpl_item_id' ),
			$conds,
			__METHOD__
		);

		return array_map( function( $item ) { return $item->tpl_item_id; }, iterator_to_array( $items ) );
	}

	/**
	 * Sets the value for the label in a certain value.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 * @param string $value
	 * @return string
	 */
	public function setLabel( $langCode, $value ) {
		// TODO: normalize value
		$this->data['label'][$langCode] = $value;
		return $value;
	}

	/**
	 * Sets the value for the description in a certain value.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode
	 * @param string $value
	 * @return string
	 */
	public function setDescription( $langCode, $value ) {
		// TODO: normalize value
		$this->data['description'][$langCode] = $value;
		return $value;
	}

	/**
	 * Remove label for a specific language in an item
	 * TODO: Check the call syntx
	 * 
	 * @since 0.1
	 * 
	 * @param string|array $languages note that an empty array removes labels for no languages while a null pointer removes all
	 */
	public function removeLabel( $languages = array() ) {
		$this->removeMultilangTexts( 'label', (array)$languages );
	}

	/**
	 * Remove descriptions for a specific language in an item
	 * TODO: Check the call syntx
	 * 
	 * @since 0.1
	 * 
	 * @param string|array $languages note that an empty array removes descriptions for no languages while a null pointer removes all
	 */
	public function removeDescription( $languages = array() ) {
		$this->removeMultilangTexts( 'description', (array)$languages );
	}

	/**
	 * Remove the value with a field specifier
	 *
	 * @since 0.1
	 *
	 * @param string $fieldKey
	 * @param array|null $languages
	 */
	protected function removeMultilangTexts( $fieldKey, array $languages = null ) {
		if ( is_null( $languages ) ) {
			$this->data[$fieldKey] = array();
		}
		else {
			foreach ( $languages as $lang ) {
				unset( $this->data[$fieldKey][$lang] );
			}
		}
	}

	/**
	 * Returns the aliases for the item in the language with the specified code.
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 *
	 * @return array
	 */
	public function getAliases( $languageCode ) {
		return array_key_exists( $languageCode, $this->data['aliases'] ) ?
			$this->data['aliases'][$languageCode] : array();
 	}

	/**
	 * Returns all the aliases for the item.
	 * The result is an array with language codes pointing to an array of aliases in the language they specify.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getAllAliases() {
		return $this->data['aliases'];
	}

	/**
	 * Sets the aliases for the item in the language with the specified code.
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function setAliases( $languageCode, array $aliases ) {
		//@todo: validate the internal structure of $aliases
		$this->data['aliases'][$languageCode] = $aliases;
	}

	/**
	 * Add the provided aliases to the aliases list of the item in the language with the specified code.
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function addAliases( $languageCode, array $aliases ) {
		$this->setAliases(
			$languageCode,
			array_unique( array_merge(
				$this->getAliases( $languageCode ),
				$aliases
			) )
		);
	}

	/**
	 * Removed the provided aliases from the aliases list of the item in the language with the specified code.
	 *
	 * @since 0.1
	 *
	 * @param $languageCode
	 * @param array $aliases
	 */
	public function removeAliases( $languageCode, array $aliases ) {
		$this->setAliases(
			$languageCode,
			array_diff(
				$this->getAliases( $languageCode ),
				$aliases
			)
		);
	}

	/**
	 * Get descriptions for an item
	 * 
	 * @since 0.1
	 * 
	 * @param array|null $languages note that an empty array gives descriptions for no languages whil a null pointer gives all
	 * 
	 * @return array found descriptions in given languages
	 */
	public function getDescriptions( array $languages = null ) {
		return $this->getMultilangTexts( 'description', $languages );
	}
	
	/**
	 *  Get labels for an item
	 *
	 * @since 0.1
	 *
	 * @param array|null $languages note that an empty array gives labels for no languages while a null pointer gives all
	 * 
	 * @return array found labels in given languages
	 */
	public function getLabels( array $languages = null ) {
		return $this->getMultilangTexts( 'label', $languages );
	}
	
	/**
	 * Get texts from an item with a field specifier.
	 *
	 * @since 0.1
	 *
	 * @param string $fieldKey
	 * @param array|null $languages
	 *
	 * @return array
	 */
	protected function getMultilangTexts( $fieldKey, array $languages = null ) {
		$textList = $this->data[$fieldKey];

		// This is compat code for the old internal layout.
		// TODO: Should be removed before we go into production.
		foreach ( $textList as $text ) {
			if ( is_array( $text ) ) {
				$textList[$text['language']] = $text['value'];
			}
		}

		if ( !is_null( $languages ) ) {
			$textList = array_intersect_key( $textList, array_flip( $languages ) );
		}

		return $textList;
	}

	/**
	 * Adds a site link.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 * @param string $updateType
	 *
	 * @return array|false Returns array on success, or false on failure
	 */
	public function addSiteLink( $siteId, $pageName, $updateType = 'add' ) {
		// TODO: This should be removed before code goes into production
		if ( Settings::get( 'blockDuplicateSiteLinks' ) ) {
			// TODO Checks if the link to be added already exists. Should give a better error message.
			// This really should have a solution and not only a quick fix
			$exists = self::getIdForSiteLink( $siteId, $pageName );
			if ( $exists !== false ) {
				return false;
			}
		}

		$success =
			( $updateType === 'add' && !array_key_exists( $siteId, $this->data['links'] ) )
			|| ( $updateType === 'update' && array_key_exists( $siteId, $this->data['links'] ) )
			|| ( $updateType === 'set' );
			
		if ( $success ) {
			$this->data['links'][$siteId] = $pageName;
		}

		// TODO: we should not return this array here like this. Probably create new object to represent link.
		return $success ? array( 'site' => $siteId, 'title' => $this->data['links'][$siteId] ) : false;
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
	protected function getLinkSiteConds( $siteId, $pageName ) {
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
		$success = array_key_exists( $siteId, $this->data['links'] ) && $this->data['links'][$siteId] === $pageName;

		if ( $success ) {
			unset( $this->data['links'][$siteId] );
		}

		return $success;

//		$dbw = wfGetDB( DB_MASTER );
//
//		return $dbw->delete(
//			'wb_items_per_site',
//			$this->getLinkSiteConds( $siteId, $pageName ),
//			__METHOD__
//		);
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
		// This is compat code for the old internal layout.
		// TODO: Should be removed before we go into production.
		if ( array_key_exists( $langCode, $this->data['description'] ) && is_array( $this->data['description'][$langCode] ) ) {
			$this->data['description'][$langCode] = $this->data['description'][$langCode]['value'];
		}

		return array_key_exists( $langCode, $this->data['description'] )
			? $this->data['description'][$langCode] : false;
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
		// This is compat code for the old internal layout.
		// TODO: Should be removed before we go into production.
		if ( array_key_exists( $langCode, $this->data['label'] ) && is_array( $this->data['label'][$langCode] ) ) {
			$this->data['label'][$langCode] = $this->data['label'][$langCode]['value'];
		}

		return array_key_exists( $langCode, $this->data['label'] )
			? $this->data['label'][$langCode] : false;
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
		// This is compat code for the old internal layout.
		// TODO: Should be removed before we go into production.
		foreach ( $this->data['links'] as $link ) {
			if ( is_array($link) ) {
				$this->data['links'][$link['site']] = $link['title'];
			}
		}

		return $this->data['links'];
	}

	/**
	 * @return String a string representing the content in a way useful for building a full text search index.
	 *		 If no useful representation exists, this method returns an empty string.
	 */
	public function getTextForSearchIndex() {
		return ''; #TODO: recursively collect all values from all properties.
	}

	/**
	 * @return String the wikitext to include when another page includes this  content, or false if the content is not
	 *		 includable in a wikitext page.
	 */
	public function getWikitextForTransclusion() {
		return false;
	}

	/**
	 * Returns a textual representation of the content suitable for use in edit summaries and log messages.
	 *
	 * @param int $maxlength maximum length of the summary text
	 * @return String the summary text
	 */
	public function getTextForSummary( $maxlength = 250 ) {
		return $this->getDescription( $GLOBALS['wgLang']->getCode() );
	}

	/**
	 * Returns native represenation of the data. Interpretation depends on the data model used,
	 * as given by getDataModel().
	 *
	 * @return mixed the native representation of the content. Could be a string, a nested array
	 *		 structure, an object, a binary blob... anything, really.
	 */
	public function getNativeData() {
		return $this->toArray();
	}

	/**
	 * returns the content's nominal size in bogo-bytes.
	 *
	 * @return int
	 */
	public function getSize()  {
		return strlen( serialize( $this->getNativeData() ) ); #TODO: keep and reuse value, content object is immutable!
	}

	/**
	 * Returns true if this content is countable as a "real" wiki page, provided
	 * that it's also in a countable location (e.g. a current revision in the main namespace).
	 *
	 * @param boolean $hasLinks: if it is known whether this content contains links, provide this information here,
	 *						to avoid redundant parsing to find out.
	 * @return boolean
	 */
	public function isCountable( $hasLinks = null ) {
		// TODO: implement
		return false;
		//return !empty( $this->data[ WikibaseContent::PROP_DESCRIPTION ] ); #TODO: better/more methods
	}

	/**
	 * @return boolean
	 */
	public function isEmpty()  {
		// TODO: might want to have better handling for this.
		// What does it mean for an item to be empty?
		// Certainly not the current check as it can have elements that are empty arrays, making base array non-empty.
		$data = $this->toArray();
		return empty( $data );
	}

	/**
	 * @since 0.1
	 * @see Content::copy
	 * @return Item
	 */
	public function copy() {
		$array = array();

		foreach ( $this->toArray() as $key => $value ) {
			$array[$key] = is_object( $value ) ? clone $value : $value;
		}

		return new self( $array );
	}

	/**
	 * Returns the WikiPage for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return WikiPage|false
	 * @todo: page based operations must be factored out of this class; they are only meaningful in the repo.
	 */
	public function getWikiPage() {
		if ( $this->wikiPage === false ) {
			$this->wikiPage = $this->isNew() ? false : self::getWikiPageForId( $this->getId() );
		}

		return $this->wikiPage;
	}

	/**
	 * Returns the Title for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return Title|false
	 * @todo: page based operations must be factored out of this class; they are only meaningful in the repo.
	 */
	public function getTitle() {
		$wikiPage = $this->getWikiPage();
		return $wikiPage === false ? false : $wikiPage->getTitle();
	}

	/**
	 * Get the item with the provided id, or null if there is no such item.
	 *
	 * @since 0.1
	 *
	 * @param integer $itemId
	 *
	 * @return Content|null
	 * @todo: factor out into the repo and/or provide a client side implementation based on the local cache
	 */
	public static function getFromId( $itemId ) {
		// TODO: since we already did the trouble of getting a WikiPage here,
		// we probably want to keep a copy of it in the Content object.
		return self::getWikiPageForId( $itemId )->getContent();
	}

	/**
	 * Get the item corresponding to the provided site and title pair, or null if there is no such item.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return Content|null
	 * @todo: factor out into the repo and/or provide a client side implementation based on the local cache
	 */
	public static function getFromSiteLink( $siteId, $pageName ) {
		$id = self::getIdForSiteLink( $siteId, $pageName );
		return $id === false ? null : self::getFromId( $id );
	}

	/**
	 * Get the items corresponding to the provided language and label pair.
	 * A description can also be provided, in which case only the item with
	 * that description will be returned (as only element in the array).
	 *
	 * @since 0.1
	 *
	 * @param string $language
	 * @param string $label
	 * @param string|null $description
	 *
	 * @return array of Item
	 * @todo: factor out into the repo; label search is not possible on the client side.
	 */
	public static function getFromLabel( $language, $label, $description = null ) {
		$ids = self::getIdsForLabel( $language, $label, $description );
		$items = array();

		foreach ( $ids as $id ) {
			$item = self::getFromId( $id );

			if ( !is_null( $item ) ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Returns the WikiPage object for the item with provided id.
	 *
	 * @since 0.1
	 *
	 * @param integer $itemId
	 *
	 * @return WikiPage
	 * @todo: page based operations must be factored out of this class; they are only meaningful in the repo.
	 */
	public static function getWikiPageForId( $itemId ) {
		return new WikiPage( self::getTitleForId( $itemId ) );
	}

	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * @since 0.1
	 *
	 * @param integer $itemId
	 *
	 * @return Title
	 * @todo: page based operations must be factored out of this class; they are only meaningful in the repo.
	 */
	public static function getTitleForId( $itemId ) {
		$id = intval( $itemId );

		if ( $id <= 0 ) {
			throw new \MWException( "itemId must be a positive integer, not " . var_export( $itemId , true ) );
		}

		return Title::newFromText( 'Data:Q' . $id ); // TODO
	}

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Item
	 */
	public static function newFromArray( array $data ) {
		$item = new static( $data, true );
		$item->cleanStructure();
		return $item;
	}

	/**
	 * @since 0.1
	 *
	 * @return Item
	 */
	public static function newEmpty() {
		return self::newFromArray( array() );
	}

	/**
	 * Whatever would be more appropriate during a normalization of titles during lookup.
	 * 
	 * @since 0.1
	 *
	 * @param string $str
	 * @return string
	 */
	public static function normalize( $str ) {
		
		// ugly but works, should probably do more normalization
		// should (?) use $wgLegalTitleChars and $wgDisableTitleConversion somehow
		$str = preg_replace( '/^[\s_]+/', '', $str );
		$str = preg_replace( '/[\s_]+$/', '', $str );
		$str = preg_replace( '/[\s_]+/', ' ', $str );
		
		return $str;
	}

}
