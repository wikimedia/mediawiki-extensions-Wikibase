<?php

namespace Wikibase\Client\Changes;

use Exception;
use InvalidArgumentException;
use MWException;
use Title;
use Wikibase\Change;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\SiteLinkCommentCreator;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this service which then takes care handling
 * it.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeHandler {

	/**
	 * The change requites any rendered version of the page to be purged from the parser cache.
	 */
	const PARSER_PURGE_ACTION = 'parser';

	/**
	 * The change requites a LinksUpdate job to be scheduled to update any links
	 * associated with the page.
	 */
	const LINKS_UPDATE_ACTION = 'links';

	/**
	 * The change requites any HTML output generated from the page to be purged from web cached.
	 */
	const WEB_PURGE_ACTION = 'web';

	/**
	 * The change requites an entry to be injected into the recentchanges table.
	 */
	const RC_ENTRY_ACTION = 'rc';

	/**
	 * The change requites an entry to be injected into the revision table.
	 */
	const HISTORY_ENTRY_ACTION = 'history';

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var AffectedPagesFinder
	 */
	private $affectedPagesFinder;

	/**
	 * @var Client\Store\TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var PageUpdater $updater
	 */
	private $updater;

	/**
	 * @var ChangeListTransformer
	 */
	private $changeListTransformer;

	/**
	 * @var string
	 */
	private $localSiteId;

	public function __construct(
		AffectedPagesFinder $affectedPagesFinder,
		TitleFactory $titleFactory,
		PageUpdater $updater,
		ChangeListTransformer $changeListTransformer,
		$localSiteId,
		$injectRC
	) {
		$this->changeListTransformer = $changeListTransformer;
		$this->affectedPagesFinder = $affectedPagesFinder;
		$this->titleFactory = $titleFactory;
		$this->updater = $updater;

		if ( !is_string( $localSiteId ) ) {
			throw new InvalidArgumentException( '$localSiteId must be a string' );
		}

		if ( !is_bool( $injectRC ) ) {
			throw new InvalidArgumentException( '$injectRC must be a bool' );
		}

		$this->localSiteId = $localSiteId;
		$this->injectRC = (bool)$injectRC;
	}

	/**
	 * Handle the provided changes.
	 *
	 * @since 0.1
	 *
	 * @param Change[] $changes
	 */
	public function handleChanges( array $changes ) {
		wfProfileIn( __METHOD__ );

		$changes = $this->changeListTransformer->transformChangeList( $changes );

		if ( !wfRunHooks( 'WikibaseHandleChanges', array( $changes ) ) ) {
			wfProfileOut( __METHOD__ );
			return;
		}

		foreach ( $changes as $change ) {
			if ( !wfRunHooks( 'WikibaseHandleChange', array( $change ) ) ) {
				continue;
			}

			$this->handleChange( $change );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Main entry point for handling changes
	 *
	 * @todo: process multiple changes at once!
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 *
	 * @throws MWException
	 *
	 * @return bool
	 */
	public function handleChange( Change $change ) {
		wfProfileIn( __METHOD__ );

		$chid = self::getChangeIdForLog( $change );
		wfDebugLog( __CLASS__, __FUNCTION__ . ": handling change #$chid"
			. " (" . $change->getType() . ")" );

		$usagesPerPage = $this->affectedPagesFinder->getPagesToUpdate( $change );

		if ( empty( $usagesPerPage ) ) {
			// nothing to do
			wfDebugLog( __CLASS__, __FUNCTION__ . ": No pages to update for change #$chid." );
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": updating " . count( $usagesPerPage )
			. " page(s) for change #$chid." );

		$actionBuckets = array();

		/** @var PageEntityUsages $usages */
		foreach ( $usagesPerPage as $usages ) {
			$actions = $this->getUpdateActions( $usages->getAspects() );
			$this->updateActionBuckets( $actionBuckets, $usages->getPageId(), $actions );
		}

		foreach ( $actionBuckets as $action => $bucket ) {
			$this->applyUpdateAction( $action, $bucket, $change );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * @param string[] $aspects
	 *
	 * @return string[] A list of actions, as defined by the self::XXXX_ACTION constants.
	 */
	public function getUpdateActions( $aspects ) {
		wfProfileIn( __METHOD__ );

		$i = 0;
		$actions = array();
		$aspects = array_flip( $aspects );

		$all = isset( $aspects[EntityUsage::ALL_USAGE] );

		if ( isset( $aspects[EntityUsage::SITELINK_USAGE] ) || $all ) {
			// Link updates might be optimized to bypass parsing
			$actions[self::LINKS_UPDATE_ACTION] = ++$i;
		}

		if ( isset( $aspects[EntityUsage::LABEL_USAGE] ) || $all ) {
			$actions[self::PARSER_PURGE_ACTION] = ++$i;
		}

		if ( isset( $aspects[EntityUsage::TITLE_USAGE] ) || $all ) {
			$actions[self::PARSER_PURGE_ACTION] = ++$i;
		}

		if ( isset( $aspects[EntityUsage::OTHER_USAGE] ) || $all ) {
			$actions[self::PARSER_PURGE_ACTION] = ++$i;
		}

		// Purge caches and inject log entries if we have reason
		// to update the cached ParserOutput object in some way.
		if ( isset( $actions[self::PARSER_PURGE_ACTION] ) || isset( $actions[self::LINKS_UPDATE_ACTION] ) ) {
			$actions[self::WEB_PURGE_ACTION] = ++$i;
			$actions[self::RC_ENTRY_ACTION] = ++$i;
			$actions[self::HISTORY_ENTRY_ACTION] = ++$i;
		}

		// If we purge the parser cache, the links update is redundant.
		if ( isset( $actions[self::PARSER_PURGE_ACTION] ) ) {
			unset( $actions[self::LINKS_UPDATE_ACTION] );
		}

		return array_flip( $actions );
	}

	/**
	 * @param array[] &$buckets Map of action names to lists of page IDs. To be updated.
	 * @param int $pageId The page ID
	 * @param string[] $actions Actions to perform on the page
	 */
	private function updateActionBuckets( &$buckets, $pageId, $actions ) {
		foreach ( $actions as $action ) {
			$buckets[$action][] = $pageId;
		}
	}

	/**
	 * @param string $action
	 * @param int[] $pageIds
	 * @param EntityChange $change
	 */
	private function applyUpdateAction( $action, array $pageIds, EntityChange $change ) {
		wfProfileIn( __METHOD__ );

		$titlesToUpdate = $this->getTitlesForPageIds( $pageIds );

		if ( $action === self::PARSER_PURGE_ACTION ) {
			$this->updater->purgeParserCache( $titlesToUpdate );
		}

		if ( $action === self::WEB_PURGE_ACTION ) {
			$this->updater->purgeWebCache( $titlesToUpdate );
		}

		if ( $action === self::LINKS_UPDATE_ACTION ) {
			$this->updater->scheduleRefreshLinks( $titlesToUpdate );
		}

		if ( $this->injectRC && (  $action === self::RC_ENTRY_ACTION ) > 0 ) {
			$rcAttribs = $this->getRCAttributes( $change );

			if ( $rcAttribs !== false ) {
				//FIXME: The same change may be reported to several target pages;
				//       The comment we generate should be adapted to the role that page
				//       plays in the change, e.g. when a sitelink changes from one page to another,
				//       the link was effectively removed from one and added to the other page.
				$this->updater->injectRCRecords( $titlesToUpdate, $rcAttribs );
			}
		}

		//TODO: handling for self::HISTORY_ENTRY_ACTION goes here.
		//      should probably be $this->updater->injectHistoryRecords() or some such.

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @param int[] $pageIds
	 *
	 * @return Title[]
	 */
	private function getTitlesForPageIds( $pageIds ) {
		$titles = array();

		foreach ( $pageIds as $id ) {
			try {
				$title = $this->titleFactory->newFromID( $id );
				$titles[] = $title;
			} catch ( Exception $ex ) {
				// never mind
			}
		}

		return $titles;
	}

	/**
	 * Returns a human readable change ID, containing multiple IDs in case of a
	 * coalesced change.
	 *
	 * @param Change $change
	 *
	 * @return string
	 */
	private static function getChangeIdForLog( Change $change ) {
		$fields = $change->getFields(); //@todo: add getFields() to the interface, or provide getters!

		if ( isset( $fields['info']['change-ids'] ) ) {
			$chid = implode( '|', $fields['info']['change-ids'] );
		} else {
			$chid = $change->getId();
		}

		return $chid;
	}

	/**
	 * Constructs RC attributes for the given change
	 *
	 * @since 0.4
	 *
	 * @param EntityChange $change The Change that caused the update
	 *
	 * @return array|boolean an array of RC attributes,
	 *         or false if the change does not provide edit meta data
	 */
	private function getRCAttributes( EntityChange $change ) {
		wfProfileIn( __METHOD__ );

		$rcinfo = $change->getMetadata();

		if ( !is_array( $rcinfo ) ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		$fields = $change->getFields(); //@todo: add getFields() to the interface, or provide getters!
		$fields['entity_type'] = $change->getEntityType();

		if ( $change instanceof ItemChange ) {
			$rcinfo['comment'] = $this->getEditComment( $change );

			if ( isset( $fields['info']['changes'] ) ) {
				$rcinfo['composite-comment'] = array();

				foreach ( $fields['info']['changes'] as $part ) {
					$rcinfo['composite-comment'][] = $this->getEditComment( $part );
				}
			}
		}

		unset( $fields['info'] );

		$rcinfo = array_merge( $fields, $rcinfo );

		$params = array(
			'wikibase-repo-change' => array_merge( $fields, $rcinfo )
		);

		wfProfileOut( __METHOD__ );
		return $params;
	}

	/**
	 * Returns the comment as structured array of information, to be
	 * stored in recent change entries and used to display formatted
	 * comments for wikibase changes in recent changes, watchlist, etc.
	 *
	 * @since 0.4
	 *
	 * @param EntityChange $change the change to get a comment for
	 *
	 * @throws MWException
	 * @return array
	 */
	public function getEditComment( EntityChange $change ) {
		$commentCreator = new SiteLinkCommentCreator(
			$this->localSiteId
		);

		//FIXME: this will only work for instances of ItemChange
		$siteLinkDiff = $change->getSiteLinkDiff();
		$action = $change->getAction();
		$comment = $change->getComment();

		$editComment = $commentCreator->getEditComment( $siteLinkDiff, $action, $comment );
		if( is_array( $editComment ) && !isset( $editComment['message'] ) ) {
			throw new MWException( 'getEditComment returned an empty comment' );
		}

		return $editComment;
	}

}
