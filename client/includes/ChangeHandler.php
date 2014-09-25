<?php

namespace Wikibase;

use MWException;
use Site;
use Title;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
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
	const PARSER_PURGE_ACTION = 1;

	/**
	 * The change requites a LinksUpdate job to be scheduled to update any links
	 * associated with the page.
	 */
	const LINKS_UPDATE_ACTION = 2;

	/**
	 * The change requites any HTML output generated from the page to be purged from web cached.
	 */
	const WEB_PURGE_ACTION = 4;

	/**
	 * The change requites an entry to be injected into the recentchanges table.
	 */
	const RC_ENTRY_ACTION = 8;

	/**
	 * The change requites an entry to be injected into the revision table.
	 */
	const HISTORY_ENTRY_ACTION = 16;

	/**
	 * @var PageUpdater $updater
	 */
	private $updater;

	/**
	 * @var EntityRevisionLookup $entityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var AffectedPagesFinder
	 */
	private $affectedPagesFinder;

	/**
	 * @var Site
	 */
	private $localSite;

	public function __construct(
		EntityChangeFactory $changeFactory,
		AffectedPagesFinder $affectedPagesFinder,
		PageUpdater $updater,
		ItemUsageIndex $itemUsageIndex = null,
		$injectRC,
		$allowDataTransclusion
	) {
		if ( !$itemUsageIndex ) {
			$itemUsageIndex = $wikibaseClient->getStore()->getItemUsageIndex();
		$this->changeFactory = $changeFactory;
		$this->affectedPagesFinder = $affectedPagesFinder;
		$this->updater = $updater;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->itemUsageIndex = $itemUsageIndex;
		$this->injectRC = (bool)$injectRC;
		$this->dataTransclusionAllowed = $allowDataTransclusion;

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
				$siteGlobalId = $this->localSite->getGlobalId();

				if ( !$break && ( $change instanceof ItemChange ) ) {
					$siteLinkDiff = $change->getSiteLinkDiff();
					if ( isset( $siteLinkDiff[ $siteGlobalId ] ) ) {
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
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibasePollHandle
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

		//TODO: Actions may be per-title, depending on how the change applies to that page.
		//      We'll need on list of titles per action.
		$actions = $this->getActions( $change );

		if ( $actions === 0 ) {
			// nothing to do
			wfDebugLog( __CLASS__, __FUNCTION__ . ": No actions to take for change #$chid." );
			wfProfileOut( __METHOD__ );
			return false;
		}

		if ( $this->mirrorUpdater !== null && ( $change instanceof EntityChange ) ) {
			// keep local mirror up to date
			$this->mirrorUpdater->handleChange( $change );
		}

		$titlesToUpdate = $this->getPagesToUpdate( $change );

		if ( empty( $titlesToUpdate ) ) {
			// nothing to do
			wfDebugLog( __CLASS__, __FUNCTION__ . ": No pages to update for change #$chid." );
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": updating " . count( $titlesToUpdate )
			. " pages (actions: " . dechex( $actions ). ") for change #$chid." );

		$this->updatePages( $change, $actions, $titlesToUpdate );

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Returns the pages that need some kind of updating given the change.
	 *
	 * @param Change $change
	 *
	 * @return Title[] the titles of the pages to update
	 */
	public function getPagesToUpdate( Change $change ) {
		wfProfileIn( __METHOD__ );

		// todo inject!
		$referencedPagesFinder = new ReferencedPagesFinder(
			$this->itemUsageIndex,
			$this->namespaceChecker,
			$this->siteId,
			$this->checkPageExistence
		);

		$pagesToUpdate = $referencedPagesFinder->getPages( $change );

		wfProfileOut( __METHOD__ );

		return $pagesToUpdate;
	}

	/**
	 * Main entry point for handling changes
	 *
	 * @since    0.4
	 *
	 * @param Change   $change         the change to apply to the pages
	 * @param int      $actions        a bit field of actions to take, as returned by getActions()
	 * @param Title[] $titlesToUpdate the pages to update
	 */
	public function updatePages( Change $change, $actions, array $titlesToUpdate ) {
		wfProfileIn( __METHOD__ );

		if ( ( $actions & self::PARSER_PURGE_ACTION ) > 0 ) {
			$this->updater->purgeParserCache( $titlesToUpdate );
		}

		if ( ( $actions & self::WEB_PURGE_ACTION ) > 0 ) {
			$this->updater->purgeWebCache( $titlesToUpdate );
		}

		if ( ( $actions & self::LINKS_UPDATE_ACTION ) > 0 ) {
			$this->updater->scheduleRefreshLinks( $titlesToUpdate );
		}

		/* @var Title $title */
		foreach ( $titlesToUpdate as $title ) {
			if ( $this->injectRC && ( $actions & self::RC_ENTRY_ACTION ) > 0 ) {
				$rcAttribs = $this->getRCAttributes( $change );

				if ( $rcAttribs !== false ) {
					$this->updater->injectRCRecord( $title, $rcAttribs );
				} else {
					trigger_error( "change #" . self::getChangeIdForLog( $change )
						. " did not provide RC info", E_USER_WARNING );
				}
			}

			//TODO: handling for self::HISTORY_ENTRY_ACTION goes here.
			//      should probably be $this->updater->injectHistoryRecords() or some such.
		}

		wfProfileOut( __METHOD__ );
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
	 * Determine which actions to take for the given change.
	 *
	 * @since 0.4
	 *
	 * @param Change $change the change to get the action for
	 *
	 * @return int actions to take, as a bit field using the XXX_ACTION flags
	 */
	public function getActions( Change $change ) {
		wfProfileIn( __METHOD__ );

		$actions = 0;

		if ( $change instanceof ItemChange ) {
			$diff = $change->getDiff();

			if ( $diff instanceof ItemDiff && !$diff->getSiteLinkDiff()->isEmpty() ) {
				//TODO: make it so we don't have to re-render
				//      if only the site links changed (see bug 45534)
				$actions |= self::PARSER_PURGE_ACTION | self::WEB_PURGE_ACTION | self::LINKS_UPDATE_ACTION
					| self::RC_ENTRY_ACTION | self::HISTORY_ENTRY_ACTION;
			}

			if ( $this->dataTransclusionAllowed ) {
				if ( $diff instanceof EntityDiff && !$diff->getClaimsDiff()->isEmpty() ) {
					$actions |= self::PARSER_PURGE_ACTION | self::WEB_PURGE_ACTION | self::LINKS_UPDATE_ACTION
						| self::RC_ENTRY_ACTION | self::HISTORY_ENTRY_ACTION;
				}

				if ( $diff instanceof EntityDiff && !$diff->getLabelsDiff()->isEmpty() ) {
					$actions |= self::PARSER_PURGE_ACTION | self::WEB_PURGE_ACTION | self::LINKS_UPDATE_ACTION
						| self::RC_ENTRY_ACTION | self::HISTORY_ENTRY_ACTION;
				}
			}
		}

		wfProfileOut( __METHOD__ );
		return $actions;
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
			$this->localSite->getGlobalId()
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
