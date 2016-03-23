<?php

namespace Wikibase\Client\Changes;

use Exception;
use MWException;
use Wikibase\Change;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * ChangeListTransformer implementation that combines runs of changes into a single change.
 * A "run" of changes is a sequence of consecutive changes performed by the same
 * user, and not interrupted by a "disruptive" change. Changes altering the association
 * between pages on the local wiki and items on the repo are considered disruptive.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChangeRunCoalescer implements ChangeListTransformer {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var string
	 */
	private $localSiteId;

	/**
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityChangeFactory $changeFactory
	 * @param string $localSiteId
	 */
	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		EntityChangeFactory $changeFactory,
		$localSiteId
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->changeFactory = $changeFactory;
		$this->localSiteId = $localSiteId;
	}

	/**
	 * Processes the given list of changes, combining any runs of changes into a single change.
	 * See the class level documentation for more details on change runs.
	 *
	 * @param Change[] $changes
	 *
	 * @return Change[]
	 */
	public function transformChangeList( array $changes ) {
		$coalesced = array();

		$changesByEntity = $this->groupChangesByEntity( $changes );
		foreach ( $changesByEntity as $entityChanges ) {
			$entityChanges = $this->coalesceRuns( $entityChanges );
			$coalesced = array_merge( $coalesced, $entityChanges );
		}

		usort( $coalesced, array( $this, 'compareChangesByTimestamp' ) );

		wfDebugLog( __CLASS__, __METHOD__ . ': coalesced '
			. count( $changes ) . ' into ' . count( $coalesced ) . ' changes' );

		return $coalesced;
	}

	/**
	 * Group changes by the entity they were applied to.
	 *
	 * @param EntityChange[] $changes
	 *
	 * @return array[] an associative array using entity IDs for keys. Associated with each
	 *         entity ID is the list of changes performed on that entity.
	 */
	private function groupChangesByEntity( array $changes ) {
		$groups = array();

		foreach ( $changes as $change ) {
			$id = $change->getEntityId()->getSerialization();

			if ( !isset( $groups[$id] ) ) {
				$groups[$id] = array();
			}

			$groups[$id][] = $change;
		}

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
	 * @param EntityChange[] $changes The changes to combine.
	 *
	 * @throws MWException
	 * @return Change a combined change representing the activity from all the original changes.
	 */
	private function mergeChanges( array $changes ) {
		if ( empty( $changes ) ) {
			return null;
		} elseif ( count( $changes ) === 1 ) {
			return reset( $changes );
		}

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
		$latestRevId = $lastmeta['rev_id'];

		$entityRev = $this->entityRevisionLookup->getEntityRevision( $entityId, $latestRevId );

		if ( !$entityRev ) {
			// XXX: EVIL HACK!
			$entityRev = $this->entityRevisionLookup->getEntityRevision( $entityId, EntityRevisionLookup::LATEST_FROM_MASTER );
			if ( $latestRevId !== $entityRev->getRevisionId() ) {
				$entityRev = null;
			}
		}

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
	 * @param EntityChange[] $changes
	 *
	 * @return EntityChange[] grouped changes
	 */
	private function coalesceRuns( array $changes ) {
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
						// TODO: don't break if only the link's badges changed
						$break = true;
						$breakNext = true;
					}
				}

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
				// skip any change that failed to process in some way (bug T51417)
			} catch ( Exception $ex ) {
				wfLogWarning( __METHOD__ . ':' . $ex->getMessage() );
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

		return $coalesced;
	}

	/**
	 * Compares two changes based on their timestamp.
	 *
	 * @param Change $a
	 * @param Change $b
	 *
	 * @return int
	 */
	public function compareChangesByTimestamp( Change $a, Change $b ) {
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

}
