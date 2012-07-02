<?php

namespace Wikibase;
use Title, WikiPage, User, MWException, Content, Status;

/**
 * Content object for articles representing Wikibase items.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemContent extends EntityContent {

	/**
	 * @since 0.1
	 * @var Item
	 */
	protected $item;

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
	 * @param Item $item
	 */
	public function __construct( Item $item ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );

		$this->item = $item;
	}

	/**
	 * Create a new ItemContent object for the provided Item.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 *
	 * @return ItemContent
	 */
	public static function newFromItem( Item $item ) {
		return new static( $item );
	}

	/**
	 * Create a new ItemContent object from the provided Item data.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return ItemContent
	 */
	public static function newFromArray( array $data ) {
		return new static( new ItemObject( $data ) );
	}

	/**
	 *
	 *
	 * @since 0.1
	 *
	 * @return Item
	 */
	public function getItem() {
		return $this->item;
	}

	/**
	 *
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 */
	public function setItem( Item $item ) {
		$this->item = $item;
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

		// TODO: this can work obtaining only a single row
		// TODO: this can be batched

		foreach ( $this->item->getSiteLinks() as $siteId => $pageName ) {
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
				$ipsId = (int)$row->ips_item_id;
				$itemId = $this->item->getId();

				if ( $ipsId !== $itemId ) {
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
	 * Checks whether the user can perform the given action.
	 *
	 * Shorthand for $this->checkPermission( $permission )->isOK();
	 *
	 * @param String    $permission         the permission to check
	 * @param null|User $user               the user to check for. If omitted, $wgUser is checked.
	 * @param bool      $doExpensiveQueries whether to perform expensive checks (default: true). May be set to false for
	 *                                      non-critical checks.
	 *
	 * @return bool True if the user has the given permission, false otherwise.
	 */
	public function userCan( $permission, User $user = null, $doExpensiveQueries = true ) {
		return $this->checkPermission( $permission, $user, $doExpensiveQueries )->isOK();
	}

	/**
	 * Checks whether the user can perform the given action.
	 *
	 * @param String    $permission         the permission to check
	 * @param null|User $user               the user to check for. If omitted, $wgUser is checked.
	 * @param bool      $doExpensiveQueries whether to perform expensive checks (default: true). May be set to false for
	 *                                      non-critical checks.
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function checkPermission( $permission, User $user = null, $doExpensiveQueries = true ) {
		global $wgUser;
		static $dummyTitle = null;

		if ( !$user ) {
			$user = $wgUser;
		}

		$title = $this->getTitle();
		$errors = null;

		if ( !$title ) {
			if ( !$dummyTitle ) {
				$dummyTitle = Title::makeTitleSafe( WB_NS_DATA, '/' );
			}

			$title = $dummyTitle;

			if ( $permission == 'edit' ) {
				// when checking for edit rights on an item that doesn't yet exists, check create rights first.

				$errors = $title->getUserPermissionsErrors( 'createpage', $user, $doExpensiveQueries );
			}
		}

		if ( empty( $errors ) ) {
			// only do this if we don't already have errors from an earlier check, to avoid redundant messages
			$errors = $title->getUserPermissionsErrors( $permission, $user, $doExpensiveQueries );
		}

		$status = Status::newGood();

		foreach ( $errors as $error ) {
			call_user_func_array( array( $status, 'error'), $error );
			$status->setResult( false );
		}

		return $status;
	}

	/**
	 * Load the item data from the database, overriding the data currently set.
	 *
	 * @since 0.1
	 *
	 * @throws MWException
	 */
	public function reload() {
		if ( !$this->isNew() ) {
			$itemContent = self::getFromId( $this->item->getId() );

			if ( is_null( $itemContent ) ) {
				throw new MWException( 'Attempt to reload item failed because it could not be obtained from the db.' );
			}

			$this->item = $itemContent->getItem();
		}
	}

	/**
	 * Saves the primary fields in the wb_items table.
	 * If the item does not exist yet (ie the id is null), it will be inserted, and the id will be set.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
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
				$this->item->setId( $dbw->insertId() );
			}
		}
		elseif ( !empty( $fields ) ) {
			$success = $dbw->update(
				'wb_items',
				$fields,
				array( 'item_id' => $this->item->getId() ),
				__METHOD__
			);
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
	 * @return String a string representing the content in a way useful for building a full text search index.
	 */
	public function getTextForSearchIndex() {
		$text = implode( "\n", $this->item->getLabels() );

		foreach ( $this->item->getAllAliases() as $aliases ) {
			$text .= "\n" . implode( "\n", $aliases );
		}

		return $text;
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
		return $this->item->getDescription( $GLOBALS['wgLang']->getCode() );
	}

	/**
	 * Returns native representation of the data. Interpretation depends on the data model used,
	 * as given by getDataModel().
	 *
	 * @return mixed the native representation of the content. Could be a string, a nested array
	 *		 structure, an object, a binary blob... anything, really.
	 */
	public function getNativeData() {
		return $this->item->toArray();
	}

	/**
	 * returns the content's nominal size in bogo-bytes.
	 *
	 * @return int
	 */
	public function getSize()  {
		return strlen( serialize( $this->getNativeData() ) );
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
	}

	/**
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty()  {
		return $this->item->isEmpty();
	}

	/**
	 * @see Content::copy
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public function copy() {
		$array = array();

		foreach ( $this->item->toArray() as $key => $value ) {
			$array[$key] = is_object( $value ) ? clone $value : $value;
		}

		return new static( new ItemObject( $array ) );
	}

	/**
	 * Returns the WikiPage for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return WikiPage|false
	 */
	public function getWikiPage() {
		if ( $this->wikiPage === false ) {
			$this->wikiPage = $this->isNew() ? false : static::getWikiPageForId( $this->item->getId() );
		}

		return $this->wikiPage;
	}

	/**
	 * Returns the Title for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return Title|false
	 */
	public function getTitle() {
		$wikiPage = $this->getWikiPage();
		return $wikiPage === false ? false : $wikiPage->getTitle();
	}

	/**
	 * Returns if the item has an ID set or not.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isNew() {
		return is_null( $this->getItem()->getId() );
	}

	/**
	 * Get the item with the provided id, or null if there is no such item.
	 *
	 * @since 0.1
	 *
	 * @param integer $itemId
	 *
	 * @return ItemContent|null
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
	 * @return ItemContent|null
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
	 * @return array of ItemContent
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
	 * @throws MWException
	 * @return Title
	 */
	public static function getTitleForId( $itemId ) {
		$id = intval( $itemId );

		if ( $id <= 0 ) {
			throw new MWException( 'itemId must be a positive integer, not ' . var_export( $itemId , true ) );
		}

		return Title::newFromText( 'Data:Q' . $id ); // TODO
	}

	/**
	 * Returns a new empty ItemContent.
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public static function newEmpty() {
		return new static( ItemObject::newEmpty() );
	}

}