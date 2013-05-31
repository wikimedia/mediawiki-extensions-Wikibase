<?php

namespace Wikibase;
use Title, WikiPage, User, MWException, Content, Status, ParserOptions, ParserOutput, DataUpdate;
use \ValueFormatters\ValueFormatterFactory;

/**
 * Content object for articles representing Wikibase items.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
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
		return new static( new Item( $data ) );
	}

	/**
	 * Returns the Item that makes up this ItemContent.
	 *
	 * @since 0.1
	 *
	 * @return Item
	 */
	public function getItem() {
		return $this->item;
	}

	/**
	 * Sets the Item that makes up this ItemContent.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 */
	public function setItem( Item $item ) {
		$this->item = $item;
	}

	/**
	 * @see Content::prepareSave
	 *
	 * @since 0.1
	 *
	 * @param WikiPage $page
	 * @param int      $flags
	 * @param int      $baseRevId
	 * @param User     $user
	 *
	 * @return Status
	 */
	public function prepareSave( WikiPage $page, $flags, $baseRevId, User $user ) {
		wfProfileIn( __METHOD__ );
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );

		if ( $status->isOK() ) {
			//NOTE: use master database to check for conflicts before saving.
			//TODO: Content::prepareSave should take a database object as a parameter,
			//      so we wouldn't need to resort to global state here.
			$dbw = wfGetDB( DB_MASTER );
			$this->addSiteLinkConflicts( $status, $dbw );
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Adds any sitelink conflicts to the status.
	 *
	 * @since 0.1
	 *
	 * @param Status $status
	 * @param \DatabaseBase|null $db The database object to use (optional).
	 *        If conflict checking is performed as part of a save operation,
	 *        this should be used to provide the master DB connection that will
	 *        also be used for saving. This will preserve transactional integrity
	 *        and avoid race conditions.
	 */
	protected function addSiteLinkConflicts( Status $status, \DatabaseBase $db = null ) {
		wfProfileIn( __METHOD__ );

		$conflicts = StoreFactory::getStore()->newSiteLinkCache()->getConflictsForItem( $this->getItem(), $db );

		foreach ( $conflicts as $conflict ) {
			$msg = $this->getConflictMessage( $conflict );

			$status->fatal( $msg );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Get Message for a conflict
	 *
	 * @since 0.4
	 *
	 * @param array $conflict
	 *
	 * @return \Message
	 */
	protected function getConflictMessage( array $conflict ) {
		$id = new EntityId( Item::ENTITY_TYPE, $conflict['itemId'] );

		/**
		 * @var WikiPage $ipsPage
		 */
		$conflictingPage = EntityContentFactory::singleton()->getWikiPageForId( $id );

		$siteSqlStore = \SiteSQLStore::newInstance();
		$site = $siteSqlStore->getSite( $conflict['siteId'] );
		$pageUrl = $site->getPageUrl( $conflict['sitePage'] );

		$msg = new \Message( 'wikibase-error-sitelink-already-used' );
		$msg->rawParams( $pageUrl );
		$msg->params( array( $conflict['sitePage'], $conflictingPage->getTitle()->getFullText() ) );

		return $msg;
	}

	/**
	 * Deletes the item.
	 *
	 * @since 0.1
	 *
	 * @param $reason string delete reason for deletion log
	 * @param bool|int $suppress int bitfield
	 *     Revision::DELETED_TEXT
	 *     Revision::DELETED_COMMENT
	 *     Revision::DELETED_USER
	 *     Revision::DELETED_RESTRICTED
	 * @param $id int article ID
	 * @param $commit boolean defaults to true, triggers transaction end
	 * @param Array|string $error
	 * @param $user User The deleting user
	 *
	 * @return int: One of WikiPage::DELETE_* constants
	 */
	public function delete( $reason = '', $suppress = false, $id = 0, $commit = true, &$error = '', User $user = null ) {
		return $this->getWikiPage()->doDeleteArticleReal( $reason, $suppress, $id, $commit, $error, $user );
	}

	/**
	 * Returns a new empty ItemContent.
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public static function newEmpty() {
		return new static( Item::newEmpty() );
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
	 * @param \WikiPage $page
	 * @param null|\ParserOutput $parserOutput
	 *
	 * @since 0.1
	 *
	 * @return DataUpdate[]
	 */
	public function getDeletionUpdates( \WikiPage $page, \ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $page, $parserOutput ),
			array( new ItemDeletionUpdate( $this ) )
		);
	}

	/**
	 * @see ContentHandler::getSecondaryDataUpdates
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
			array( new ItemModificationUpdate( $this ) )
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
		$valueFormatters = new ValueFormatterFactory( $GLOBALS['wgValueFormatters'] );

		$itemView = new ItemView( $valueFormatters );
		return $itemView->getParserOutput( $this, $options, $generateHtml );
	}

}
