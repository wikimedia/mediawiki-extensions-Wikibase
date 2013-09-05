<?php
namespace Wikibase;

use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Site;
use SiteList;
use Sites;
use Title;
use Wikibase\Client\WikibaseClient;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this service which then takes care handling
 * it.
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
 * @ingroup WikibaseLib
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
	 * Returns the global instance of the ChangeHandler interface.
	 *
	 * @since 0.1
	 *
	 * @return ChangeHandler
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * @var PageUpdater $updater
	 */
	protected $updater;


	/**
	 * @var EntityLookup $entityLookup
	 */
	protected $entityLookup;

	/**
	 * @var \Site $site
	 */
	protected $site;

	/**
	 * @var NamespaceChecker $namespaceChecker
	 */
	protected $namespaceChecker;

	/**
	 * @var bool
	 */
	protected $checkPageExistence = true;

	public function __construct( PageUpdater $updater = null,
			EntityLookup $entityLookup = null,
			EntityUsageIndex $entityUsageIndex = null,
			Site $localSite = null,
			SiteList $sites = null) {

		wfProfileIn( __METHOD__ );

		if ( $sites === null ) {
			$sites = Sites::singleton()->getSites();
		}

		$this->sites = $sites;

		if ( !$updater ) {
			$updater = new WikiPageUpdater();
		}

		if ( !$entityLookup ) {
			$entityLookup = WikibaseClient::getDefaultInstance()->getStore()->getEntityLookup();
		}

		if ( !$entityUsageIndex ) {
			$entityUsageIndex = WikibaseClient::getDefaultInstance()->getStore()->getEntityUsageIndex();
		}

		if ( !$localSite ) {
			//XXX: DB lookup in a constructor, ugh
			$siteGlobalId = Settings::get( 'siteGlobalID' );
			$localSite = $this->sites->getSite( $siteGlobalId );

			if ( $localSite === null ) {
				throw new MWException( "Unknown site ID configured: $siteGlobalId" );
			}
		}

		$this->site = $localSite;
		$this->updater = $updater;
		$this->entityLookup = $entityLookup;
		$this->entityUsageIndex = $entityUsageIndex;

		// TODO: allow these to be passed in as parameters!
		$this->setNamespaces(
			Settings::get( 'namespaces' ),
			Settings::get( 'excludeNamespaces' )
		);

		$this->injectRC = Settings::get( 'injectRecentChanges' );

		if ( Settings::get( 'repoDatabase' ) === null ) {
			$this->mirrorUpdater = new EntityCacheUpdater( new EntityCacheTable() );
		} else {
			$this->mirrorUpdater = null;
		}

		$this->dataTransclusionAllowed = Settings::get( 'allowDataTransclusion' );
		$this->actionMask = 0xFFFF; //TODO: use changeHanderActions setting

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Enable or disable page existence checks. Useful for unit tests.
	 *
	 * @param boolean $checkPageExistence
	 */
	public function setCheckPageExistence( $checkPageExistence ) {
		$this->checkPageExistence = $checkPageExistence;
	}

	/**
	 * Set the namespaces to include or exclude.
	 *
	 * @param int[] $include a list of namespace IDs to include
	 * @param int[] $exclude a list of namespace IDs to exclude
	 */
	public function setNamespaces( array $include, array $exclude = array() ) {
		$this->namespaceChecker = new NamespaceChecker(
			$exclude,
			$include
		);
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
			$id = $change->getEntityId()->getPrefixedId();

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

		$entity = $this->entityLookup->getEntity( $entityId, $latestRevId );

		if ( !$entity ) {
			throw new MWException( "Failed to load revision $latestRevId of $entityId" );
		}

		$parent = $parentRevId ? $this->entityLookup->getEntity( $entityId, $parentRevId ) : null;

		//XXX: we could avoid loading the entity data by merging the diffs programatically
		//     instead of re-calculating.
		$change = EntityChange::newFromUpdate(
			$parent ? EntityChange::UPDATE : EntityChange::ADD,
			$parent,
			$entity
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
				$siteGlobalId = $this->site->getGlobalId();

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
				wfLogWarning( __CLASS__, __METHOD__ . ':' . $e->getMessage() );
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

	// ==========================================================================================

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
	 * @todo: determine actions for each page!
	 *
	 * @param Change $change
	 *
	 * @return Title[] the titles of the pages to update
	 */
	public function getPagesToUpdate( Change $change ) {
		wfProfileIn( __METHOD__ );

		$pagesToUpdate = array();

		if ( $change instanceof ItemChange ) {
			// update local pages connected to a relevant data item.

			$itemId = $change->getEntityId();

			$siteGlobalId = $this->site->getGlobalId();

			$usedOnPages = $this->entityUsageIndex->getEntityUsage( array( $itemId ) );
			$pagesToUpdate = array_merge( $pagesToUpdate, $usedOnPages );

			// if an item's sitelinks change, update the old and the new target
			$siteLinkDiff = $change->getSiteLinkDiff();

			if ( isset( $siteLinkDiff[$siteGlobalId] ) ) {

				// $siteLinkDiff changed from containing atomic diffs to
				// containing map diffs. For B/C, handle both cases.
				$siteLinkDiffOp = $siteLinkDiff[$siteGlobalId];

				if ( $siteLinkDiffOp instanceof Diff ) {
					if ( array_key_exists( 'name', $siteLinkDiffOp ) ) {
						$siteLinkDiffOp = $siteLinkDiffOp['name'];
					} else {
						// change to badges only
						$siteLinkDiffOp = null;
					}
				}

				if ( $siteLinkDiffOp === null ) {
					// do nothing, only badges were  changed
				}elseif ( $siteLinkDiffOp instanceof DiffOpAdd ) {
					$pagesToUpdate[] = $siteLinkDiffOp->getNewValue();
				} elseif ( $siteLinkDiffOp instanceof DiffOpRemove ) {
					$pagesToUpdate[] = $siteLinkDiffOp->getOldValue();
				} elseif ( $siteLinkDiffOp instanceof DiffOpChange ) {
					$pagesToUpdate[] = $siteLinkDiffOp->getNewValue();
					$pagesToUpdate[] = $siteLinkDiffOp->getOldValue();
				} else {
					wfWarn( "Unknown change operation: " . get_class( $siteLinkDiffOp ) . ")" );
				}
			}
		}

		$pagesToUpdate = array_unique( $pagesToUpdate );
		$titlesToUpdate = array();

		foreach ( $pagesToUpdate as $page ) {
			$title = Title::newFromText( $page );

			if ( !$title->exists() && $this->checkPageExistence ) {
				continue;
			}

			$ns = $title->getNamespace();

			if ( !is_int( $ns ) || !$this->namespaceChecker->isWikibaseEnabled( $ns ) ) {
				continue;
			}

			$titlesToUpdate[] = $title;
		}

		wfProfileOut( __METHOD__ );
		return $titlesToUpdate;
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
	protected static function getChangeIdForLog( Change $change ) {
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
	protected function getRCAttributes( EntityChange $change ) {
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
				$rcinfo['composite-comment'][] = array();

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

		$actions = $actions & $this->actionMask;

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
	 * @param Change $change the change to get a comment for
	 *
	 * @return array
	 */
	public function getEditComment( Change $change ) {
		$commentCreator = new SiteLinkCommentCreator(
			$this->site->getGlobalId()
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
