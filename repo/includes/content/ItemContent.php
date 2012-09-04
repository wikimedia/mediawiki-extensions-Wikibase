<?php

namespace Wikibase;
use Title, WikiPage, User, MWException, Content, Status, ParserOptions, ParserOutput, DataUpdate;

/**
 * Content object for articles representing Wikibase items.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Content
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
		// TODO: use store

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $this->item->getSiteLinks() as $siteLink ) {
			$res = $dbw->select(
				'wb_items_per_site',
				array( 'ips_item_id' ),
				array(
					'ips_site_id' => $siteLink->getSite()->getGlobalId(),
					'ips_site_page' => $siteLink->getPage(),
				),
				__METHOD__
			);

			while ( $row = $res->fetchObject() ) {
				$ipsId = (int)$row->ips_item_id;
				$itemId = $this->item->getId();

				/**
				 * @var WikiPage $ipsPage
				 */
				$ipsPage = $this->getContentHandler()->getWikiPageForId( $ipsId );

				if ( $ipsId !== $itemId ) {
					$status->setResult( false );
					$status->error(
						'wikibase-error-sitelink-already-used',
						$siteLink->getSite()->getGlobalId(),
						$siteLink->getPage(),
						$ipsPage->getTitle()->getFullText()
					);
				}
			}
		}

		return $status->isOK();
	}

	/**
	 * Deletes the item.
	 *
	 * @since 0.1
	 *
	 * @param $reason string delete reason for deletion log
	 * @param $suppress int bitfield
	 * 	Revision::DELETED_TEXT
	 * 	Revision::DELETED_COMMENT
	 * 	Revision::DELETED_USER
	 * 	Revision::DELETED_RESTRICTED
	 * @param $id int article ID
	 * @param $commit boolean defaults to true, triggers transaction end
	 * @param &$error Array of errors to append to
	 * @param $user User The deleting user
	 *
	 * @return int: One of WikiPage::DELETE_* constants
	 */
	public function delete( $reason = '', $suppress = false, $id = 0, $commit = true, &$error = '', User $user = null ) {
		return $this->getWikiPage()->doDeleteArticleReal( $reason, $suppress, $id, $commit, $error, $user );
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
			$itemContent = $this->getContentHandler()->getFromId( $this->item->getId() );

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
		$success = true;

		if ( $this->isNew() ) {
			$idGenerator = StoreFactory::getStore()->newIdGenerator();

			try {
				$id = $idGenerator->getNewId( $this->getContentHandler()->getModelID() );
			}
			catch ( MWException $exception ) {
				$success = false;
			}

			if ( $success ) {
				$this->getEntity()->setId( $id );
			}
		}

		return $success;
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

	/**
	 * @see EntityContent::getEntity
	 *
	 * @since 0.1
	 *
	 * @return Item
	 */
	public function getEntity() {
		return $this->item;
	}

	/**
	 * @see Content::getDeletionUpdates
	 *
	 * @param \Title $title
	 * @param null|\ParserOutput $parserOutput
	 *
	 * @since 0.1
	 *
	 * @return array of \DataUpdate
	 */
	public function getDeletionUpdates( \Title $title, \ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $title, $parserOutput ),
			array( new ItemDeletionUpdate( $this ) )
		);
	}

	/**
	 * @see   ContentHandler::getSecondaryDataUpdates
	 *
	 * @since 0.1
	 *
	 * @param Title              $title
	 * @param Content|null       $old
	 * @param bool               $recursive
	 *
	 * @param null|ParserOutput  $parserOutput
	 *
	 * @return \Title of DataUpdate
	 */
	public function getSecondaryDataUpdates( Title $title, Content $old = null,
		$recursive = false, ParserOutput $parserOutput = null ) {

		return array_merge(
			parent::getSecondaryDataUpdates( $title, $old, $recursive, $parserOutput ),
			array( new ItemStructuredSave( $this ) )
		);
	}

	/**
	 * Returns a ParserOutput object containing the HTML.
	 *
	 * @since 0.1
	 *
	 * @param Title              $title
	 * @param null               $revId
	 * @param null|ParserOptions $options
	 * @param bool               $generateHtml
	 *
	 * @return \Title
	 */
	public function getParserOutput( Title $title, $revId = null, ParserOptions $options = null, $generateHtml = true )  {
		$itemView = new ItemView( ); // @todo: construct context for title?
		return $itemView->getParserOutput( $this, $options, $generateHtml );
	}
}
