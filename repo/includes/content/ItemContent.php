<?php

namespace Wikibase;
use Title, WikiPage, User, MWException, Content, Status, ParserOptions, ParserOutput, DataUpdate;

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
			$this->addSiteLinkConflicts( $status );

			// Do not run this when running test using MySQL as self joins fail on temporary tables.
			if ( !defined( 'MW_PHPUNIT_TEST' )
				|| !( StoreFactory::getStore() instanceof \Wikibase\SqlStore )
				|| wfGetDB( DB_SLAVE )->getType() !== 'mysql' ) {
				$this->addLabelDescriptionConflicts( $status );
			}
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Adds any conflicts with items that have the same label
	 * and description pair in the same language to the status.
	 *
	 * @since 0.1
	 *
	 * @param Status $status
	 */
	protected function addLabelDescriptionConflicts( Status $status ) {
		$terms = array();

		$entity = $this->getEntity();

		foreach ( $entity->getLabels() as $langCode => $labelText ) {
			$description = $entity->getDescription( $langCode );

			if ( $description !== false ) {
				$label = array(
					'termLanguage' => $langCode,
					'termText' => $labelText,
					'termType' => TermCache::TERM_TYPE_LABEL,
				);

				$description = array(
					'termLanguage' => $langCode,
					'termText' => $description,
					'termType' => TermCache::TERM_TYPE_DESCRIPTION,
				);

				$terms[] = array( $label, $description );
			}
		}

		if ( !empty( $terms ) ) {
			$foundTerms = StoreFactory::getStore()->newTermCache()->getMatchingTermCombination(
				$terms,
				null,
				$entity->getType(),
				$entity->getId(),
				$entity->getType()
			);

			if ( !empty( $foundTerms ) ) {
				list( $label, $description ) = $foundTerms;

				$status->fatal(
					'wikibase-error-label-not-unique-item',
					$label['termText'],
					$label['termLanguage'],
					$label['entityId'],
					$description['termText']
				);
			}
		}
	}

	/**
	 * Adds any sitelink conflicts to the status.
	 *
	 * @since 0.1
	 *
	 * @param Status $status
	 */
	protected function addSiteLinkConflicts( Status $status ) {
		$conflicts = StoreFactory::getStore()->newSiteLinkCache()->getConflictsForItem( $this->getItem() );

		foreach ( $conflicts as $conflict ) {
			/**
			 * @var WikiPage $ipsPage
			 */
			$conflictingPage = EntityContentFactory::singleton()->getWikiPageForId( Item::ENTITY_TYPE, $conflict['itemId'] );

			// NOTE: it would be nice to generate the link here and just pass it as HTML,
			// but Status forces all parameters to be escaped.
			$status->fatal(
				'wikibase-error-sitelink-already-used',
				$conflict['siteId'],
				$conflict['sitePage'],
				$conflictingPage->getTitle()->getFullText()
			);
		}
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
	 * @param \WikiPage $page
	 * @param null|\ParserOutput $parserOutput
	 *
	 * @since 0.1
	 *
	 * @return array of \DataUpdate
	 */
	public function getDeletionUpdates( \WikiPage $page, \ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $page, $parserOutput ),
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
		$itemView = new ItemView( );
		return $itemView->getParserOutput( $this, $options, $generateHtml );
	}
}
