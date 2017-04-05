<?php

namespace Wikibase\Client\Changes;

use Exception;
use Hooks;
use InvalidArgumentException;
use MWException;
use SiteLookup;
use Title;
use Wikibase\Change;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\EntityChange;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this service which then takes care handling it.
 *
 * @see docs/change-propagation.wiki for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0+
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
	 * @var AffectedPagesFinder
	 */
	private $affectedPagesFinder;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var PageUpdater
	 */
	private $updater;

	/**
	 * @var ChangeListTransformer
	 */
	private $changeListTransformer;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var string
	 */
	private $repoId;

	/**
	 * @var bool
	 */
	private $injectRecentChanges;

	/**
	 * @param AffectedPagesFinder $affectedPagesFinder
	 * @param TitleFactory $titleFactory
	 * @param PageUpdater $updater
	 * @param ChangeListTransformer $changeListTransformer
	 * @param SiteLookup $siteLookup
	 * @param string $repoId
	 * @param bool $injectRecentChanges
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		AffectedPagesFinder $affectedPagesFinder,
		TitleFactory $titleFactory,
		PageUpdater $updater,
		ChangeListTransformer $changeListTransformer,
		SiteLookup $siteLookup,
		$repoId,
		$injectRecentChanges = true
	) {
		if ( !is_bool( $injectRecentChanges ) ) {
			throw new InvalidArgumentException( '$injectRecentChanges must be a bool' );
		}

		$this->affectedPagesFinder = $affectedPagesFinder;
		$this->titleFactory = $titleFactory;
		$this->updater = $updater;
		$this->changeListTransformer = $changeListTransformer;
		$this->siteLookup = $siteLookup;
		$this->repoId = $repoId;
		$this->injectRecentChanges = $injectRecentChanges;
	}

	/**
	 * @param EntityChange[] $changes
	 */
	public function handleChanges( array $changes ) {
		$changes = $this->changeListTransformer->transformChangeList( $changes );

		if ( !Hooks::run( 'WikibaseHandleChanges', array( $changes ) ) ) {
			return;
		}

		foreach ( $changes as $change ) {
			if ( !Hooks::run( 'WikibaseHandleChange', array( $change ) ) ) {
				continue;
			}

			$this->handleChange( $change );
		}
	}

	/**
	 * Main entry point for handling changes
	 *
	 * @todo: process multiple changes at once!
	 *
	 * @param EntityChange $change
	 *
	 * @throws MWException
	 */
	public function handleChange( EntityChange $change ) {
		$changeId = $this->getChangeIdForLog( $change );
		wfDebugLog( __CLASS__, __FUNCTION__ . ": handling change #$changeId"
			. ' (' . $change->getType() . ')' );

		$usagesPerPage = $this->affectedPagesFinder->getAffectedUsagesByPage( $change );

		wfDebugLog( __CLASS__, __FUNCTION__ . ': updating ' . count( $usagesPerPage )
			. " page(s) for change #$changeId." );

		$actionBuckets = array();

		/** @var PageEntityUsages $usages */
		foreach ( $usagesPerPage as $usages ) {
			$actions = $this->getUpdateActions( $usages->getAspects() );
			$this->updateActionBuckets( $actionBuckets, $usages->getPageId(), $actions );
		}

		foreach ( $actionBuckets as $action => $bucket ) {
			$this->applyUpdateAction( $action, $bucket, $change );
		}
	}

	/**
	 * @param string[] $aspects List of usage aspects (without modifiers), as defined by the
	 * EntityUsage::..._USAGE constants.
	 *
	 * @return string[] List of actions, as defined by the ChangeHandler::..._ACTION constants.
	 */
	public function getUpdateActions( array $aspects ) {
		$actions = array();
		$aspects = array_flip( $aspects );

		$all = isset( $aspects[EntityUsage::ALL_USAGE] );

		if ( isset( $aspects[EntityUsage::SITELINK_USAGE] ) || $all ) {
			$actions[self::LINKS_UPDATE_ACTION] = true;

			// TODO: introduce an update action that updates just the metadata
			// in the cached ParserOutput, without re-parsing the page!
			$actions[self::PARSER_PURGE_ACTION] = true;
		}

		if ( isset( $aspects[EntityUsage::LABEL_USAGE] ) || $all ) {
			$actions[self::PARSER_PURGE_ACTION] = true;
		}

		if ( isset( $aspects[EntityUsage::TITLE_USAGE] ) || $all ) {
			$actions[self::PARSER_PURGE_ACTION] = true;
		}

		if ( isset( $aspects[EntityUsage::OTHER_USAGE] ) || $all ) {
			$actions[self::PARSER_PURGE_ACTION] = true;
		}

		// Purge caches and inject log entries if we have reason
		// to update the cached ParserOutput object in some way.
		if ( isset( $actions[self::PARSER_PURGE_ACTION] ) || isset( $actions[self::LINKS_UPDATE_ACTION] ) ) {
			$actions[self::WEB_PURGE_ACTION] = true;
			$actions[self::RC_ENTRY_ACTION] = true;
			$actions[self::HISTORY_ENTRY_ACTION] = true;
		}

		return array_keys( $actions );
	}

	/**
	 * @param array[] &$buckets Map of action names to lists of page IDs. To be updated.
	 * @param int $pageId The page ID
	 * @param string[] $actions Actions to perform on the page
	 */
	private function updateActionBuckets( array &$buckets, $pageId, array $actions ) {
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
		$titlesToUpdate = $this->getTitlesForPageIds( $pageIds );

		switch ( $action ) {
			case self::PARSER_PURGE_ACTION:
				$this->updater->purgeParserCache( $titlesToUpdate );
				break;

			case self::WEB_PURGE_ACTION:
				$this->updater->purgeWebCache( $titlesToUpdate );
				break;

			case self::LINKS_UPDATE_ACTION:
				$this->updater->scheduleRefreshLinks( $titlesToUpdate );
				break;

			case self::RC_ENTRY_ACTION:
				if ( $this->injectRecentChanges ) {
					$this->updater->injectRCRecords( $titlesToUpdate, $change );
				}

				break;

			//TODO: handling for self::HISTORY_ENTRY_ACTION goes here.
			//      should probably be $this->updater->injectHistoryRecords() or some such.
		}
	}

	/**
	 * @param int[] $pageIds
	 *
	 * @return Title[]
	 */
	private function getTitlesForPageIds( array $pageIds ) {
		$titles = array();

		foreach ( $pageIds as $id ) {
			try {
				$title = $this->titleFactory->newFromID( $id );
				$titles[] = $title;
			} catch ( Exception $ex ) {
				// No title for that ID, maybe the page got deleted just now.
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
	private function getChangeIdForLog( Change $change ) {
		if ( $change instanceof EntityChange ) {
			$info = $change->getInfo();

			if ( isset( $info['change-ids'] ) ) {
				return implode( '|', $info['change-ids'] );
			}
		}

		return $change->getId();
	}

}
