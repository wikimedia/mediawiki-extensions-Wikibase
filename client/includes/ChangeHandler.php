<?php

namespace Wikibase;

use Exception;
use InvalidArgumentException;
use MWException;
use Title;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this service which then takes care handling
 * it.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @fixme: ChangeNotification, ChangeHandler, ClientHooks::onWikibasePollHandle
 *         and ClientChangeHandler need to be combined and refactored.
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
	 * @var EntityRevisionLookup $entityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var string
	 */
	private $localSiteId;

	public function __construct(
		EntityChangeFactory $changeFactory,
		AffectedPagesFinder $affectedPagesFinder,
		TitleFactory $titleFactory,
		PageUpdater $updater,
		EntityRevisionLookup $entityRevisionLookup,
		$localSiteId,
		$injectRC
	) {
		$this->changeFactory = $changeFactory;
		$this->affectedPagesFinder = $affectedPagesFinder;
		$this->titleFactory = $titleFactory;
		$this->updater = $updater;
		$this->entityRevisionLookup = $entityRevisionLookup;

		if ( !is_string( $localSiteId ) ) {
			throw new InvalidArgumentException( '$localSiteId must be a string' );
		}

		if ( !is_bool( $injectRC ) ) {
			throw new InvalidArgumentException( '$injectRC must be a bool' );
		}

		$this->localSiteId = $localSiteId;
		$this->injectRC = (bool)$injectRC;

		$this->mirrorUpdater = null;
	}

	/**
	 * Group changes by the entity they were applied to.
	 *
	 * @since 0.4
	 *
	 * @param EntityChange[] $changes
	 * @return EntityChange[][] an associative array using entity IDs for keys. Associated with each
	 *         entity ID is the list of changes performed on that entity.
	 */
	public function groupChangesByEntity( array $changes ) {
		wfProfileIn( __METHOD__ );
		$groups = array();

		foreach ( $changes as $change ) {
			$id = $change->getEntityId()->getSerialization();

			if ( !isset( $groups[$id] ) ) {
				$groups[$id] = array();
			}

			$groups[$id][] = $change;
		}

		wfProfileOut( __METHOD__ );
		return $groups;
	}

	/**
	 * Combines a set of changes into one change. All changes are assume to have been performed
	 * by the same user on the same entity. They are further assumed to be UPDATE actions
	 * and sorted in causal (chronological) order.
	 *
	 * If $changes is empty, this method returns null. If $changes contains exactly one change,
	 * that change is returned. Otherwise, a combined change is returned.
	 *
	 * @since 0.4
	 *
	 * @param EntityChange[] $changes The changes to combine.
	 *
	 * @throws MWException
	 * @return Change a combined change representing the activity from all the original changes.
	 */
	public function mergeChanges( array $changes ) {
		if ( empty( $changes ) )  {
			return null;
		} elseif ( count( $changes ) === 1 )  {
			return reset( $changes );
		}

		wfProfileIn( __METHOD__ );

		// we now assume that we have a list if EntityChanges,
		// all done by the same user on the same entity.

		/* @var EntityChange $last */
		/* @var EntityChange $first */
		$last = end( $changes );
		$first = reset( $changes );

		$minor = true;
		$bot = true;

		$ids = array();

		foreach ( $changes as $change ) {
			$ids[] = $change->getId();
			$meta = $change->getMetadata();

			$minor &= isset( $meta['minor'] ) && (bool)$meta['minor'];
			$bot &= isset( $meta['bot'] ) && (bool)$meta['bot'];
		}

		$lastmeta = $last->getMetadata();
		$firstmeta = $first->getMetadata();

		$entityId = $first->getEntityId();

		$parentRevId = $firstmeta['parent_id'];
		$latestRevId = $firstmeta['rev_id'];

		$entityRev = $this->entityRevisionLookup->getEntityRevision( $entityId, $latestRevId );

		if ( !$entityRev ) {
			throw new MWException( "Failed to load revision $latestRevId of $entityId" );
		}

		$parentRev = $parentRevId ? $this->entityRevisionLookup->getEntityRevision( $entityId, $parentRevId ) : null;

		//XXX: we could avoid loading the entity data by merging the diffs programatically
		//     instead of re-calculating.
		$change = $this->changeFactory->newFromUpdate(
			$parentRev ? EntityChange::UPDATE : EntityChange::ADD,
			$parentRev === null ? null : $parentRev->getEntity(),
			$entityRev->getEntity()
		);

		$change->setFields(
			array(
				'revision_id' => $last->getField( 'revision_id' ),
				'user_id' => $last->getField( 'user_id' ),
				'object_id' => $last->getField( 'object_id' ),
				'time' => $last->getField( 'time' ),
			)
		);

		$change->setMetadata( array_merge(
			$lastmeta,
			array(
				'parent_id' => $parentRevId,
				'minor' => $minor,
				'bot' => $bot,
			)
			//FIXME: size before & size after
			//FIXME: size before & size after
		) );

		$info = $change->hasField( 'info' ) ? $change->getField( 'info' ) : array();
		$info['change-ids'] = $ids;
		$info['changes'] = $changes;
		$change->setField( 'info', $info );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * Coalesce consecutive changes by the same user to the same entity into one.
	 * A run of changes may be broken if the action performed changes (e.g. deletion
	 * instead of update) or if a sitelink pointing to the local wiki was modified.
	 *
	 * Some types of actions, like deletion, will break runs.
	 * Interleaved changes to different items will break runs.
	 *
	 * @since 0.4
	 *
	 * @param EntityChange[] $changes
	 * @return EntityChange[] grouped changes
	 */
	public function coalesceRuns( array $changes ) {
		wfProfileIn( __METHOD__ );

		$coalesced = array();

		$currentRun = array();
		$currentUser = null;
		$currentEntity = null;
		$currentAction = null;
		$breakNext = false;

		foreach ( $changes as $change ) {
			try {
				$action = $change->getAction();
				$meta = $change->getMetadata();
				$user = $meta['user_text'];
				$entityId = $change->getEntityId()->__toString();

				$break = $breakNext
					|| $currentAction !== $action
					|| $currentUser !== $user
					|| $currentEntity !== $entityId;

				$breakNext = false;

				if ( !$break && ( $change instanceof ItemChange ) ) {
					$siteLinkDiff = $change->getSiteLinkDiff();
					if ( isset( $siteLinkDiff[ $this->localSiteId ] ) ) {
						$break = true;
						$breakNext = true;
					};
				}

				// FIXME: We should call changeNeedsRendering() and see if the needs-rendering
				//        stays the same, and break the run if not. This way, uninteresting
				//        changes can be sorted out more cleanly later.
				// FIXME: Perhaps more easily, get rid of them here and now!
				if ( $break ) {
					if ( !empty( $currentRun ) ) {
						try {
							$coalesced[] = $this->mergeChanges( $currentRun );
						} catch ( MWException $ex ) {
							// Something went wrong while trying to merge the changes.
							// Just keep the original run.
							wfWarn( $ex->getMessage() );
							$coalesced = array_merge( $coalesced, $currentRun );
						}
					}

					$currentRun = array();
					$currentUser = $user;
					$currentEntity = $entityId;
					$currentAction = $action === EntityChange::ADD ? EntityChange::UPDATE : $action;
				}

				$currentRun[] = $change;
			// skip any change that failed to process in some way (bug 49417)
			} catch ( \Exception $e ) {
				wfLogWarning( __METHOD__ . ':' . $e->getMessage() );
			}
		}

		if ( !empty( $currentRun ) ) {
			try {
				$coalesced[] = $this->mergeChanges( $currentRun );
			} catch ( MWException $ex ) {
				// Something went wrong while trying to merge the changes.
				// Just keep the original run.
				wfWarn( $ex->getMessage() );
				$coalesced = array_merge( $coalesced, $currentRun );
			}
		}

		wfProfileOut( __METHOD__ );
		return $coalesced;
	}

	/**
	 * Coalesce changes where possible. This combines any consecutive changes by the same user
	 * to the same entity into one. Interleaved changes to different items are handled gracefully.
	 *
	 * @since 0.4
	 *
	 * @param EntityChange[] $changes
	 * @return Change[] grouped changes
	 */
	public function coalesceChanges( array $changes ) {
		wfProfileIn( __METHOD__ );
		$coalesced = array();

		$changesByEntity = $this->groupChangesByEntity( $changes );
		foreach ( $changesByEntity as $entityChanges ) {
			$entityChanges = $this->coalesceRuns( $entityChanges );
			$coalesced = array_merge( $coalesced, $entityChanges );
		}

		usort( $coalesced, 'Wikibase\ChangeHandler::compareChangesByTimestamp' );

		wfDebugLog( __CLASS__, __METHOD__ . ": coalesced "
			. count( $changes ) . " into " . count( $coalesced ) . " changes"  );

		wfProfileOut( __METHOD__ );
		return $coalesced;
	}

	/**
	 * Compares two changes based on their timestamp.
	 *
	 * @param Change $a
	 * @param Change $b
	 *
	 * @return Mixed
	 */
	public static function compareChangesByTimestamp( Change $a, Change $b ) {
		//NOTE: beware https://bugs.php.net/bug.php?id=50688 !

		if ( $a->getTime() > $b->getTime() ) {
			return 1;
		} elseif ( $a->getTime() < $b->getTime() ) {
			return -1;
		}

		if ( $a->getId() > $b->getId() ) {
			return 1;
		} elseif ( $a->getId() < $b->getId() ) {
			return -1;
		}

		return 0;
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

		$changes = $this->coalesceChanges( $changes );

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
	 * @param \Wikibase\EntityChange $change The Change that caused the update
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
	 * @throws \MWException
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
			throw new \MWException( 'getEditComment returned an empty comment' );
		}

		return $editComment;
	}

}
