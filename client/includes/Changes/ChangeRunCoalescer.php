<?php

namespace Wikibase\Client\Changes;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Exception;
use MWException;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;

/**
 * A transformer for lists of EntityChanges that combines runs of changes into a single change.
 * A "run" of changes is a sequence of consecutive changes performed by the same
 * user, and not interrupted by a "disruptive" change. Changes altering the association
 * between pages on the local wiki and items on the repo are considered disruptive.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeRunCoalescer {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $localSiteId;

	/**
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityChangeFactory $changeFactory
	 * @param LoggerInterface $logger
	 * @param string $localSiteId
	 */
	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		EntityChangeFactory $changeFactory,
		LoggerInterface $logger,
		$localSiteId
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->changeFactory = $changeFactory;
		$this->logger = $logger;
		$this->localSiteId = $localSiteId;
	}

	/**
	 * Processes the given list of changes, combining any runs of changes into a single change.
	 * See the class level documentation for more details on change runs.
	 *
	 * @param EntityChange[] $changes
	 *
	 * @return EntityChange[]
	 */
	public function transformChangeList( array $changes ) {
		$coalesced = [];

		$changesByEntity = $this->groupChangesByEntity( $changes );
		/** @var EntityChange[] $entityChanges */
		foreach ( $changesByEntity as $entityChanges ) {
			$entityChanges = $this->coalesceRuns( $entityChanges[0]->getEntityId(), $entityChanges );
			$coalesced = array_merge( $coalesced, $entityChanges );
		}

		usort( $coalesced, [ $this, 'compareChangesByTimestamp' ] );
		$this->logger->debug(
			'{method}: coalesced {changeCount} into {changeCoalescedCount} changes',
			[
				'method' => __METHOD__,
				'changeCount' => count( $changes ),
				'changeCoalescedCount' => count( $coalesced ),
			]
		);

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
		$groups = [];

		foreach ( $changes as $change ) {
			$id = $change->getEntityId()->getSerialization();

			if ( !isset( $groups[$id] ) ) {
				$groups[$id] = [];
			}

			$groups[$id][] = $change;
		}

		return $groups;
	}

	/**
	 * Combines a set of changes into one change. All changes are assumed to have been performed
	 * by the same user on the same entity. They are further assumed to be UPDATE actions
	 * and sorted in causal (chronological) order.
	 *
	 * If $changes contains exactly one change, that change is returned. Otherwise, a combined
	 * change is returned.
	 *
	 * @param EntityId $entityId
	 * @param EntityChange[] $changes The changes to combine.
	 *
	 * @throws MWException
	 * @return Change a combined change representing the activity from all the original changes.
	 */
	private function mergeChanges( EntityId $entityId, array $changes ) {
		if ( count( $changes ) === 1 ) {
			return reset( $changes );
		}

		// we now assume that we have a list if EntityChanges,
		// all done by the same user on the same entity.

		/**
		 * @var EntityChange $last
		 * @var EntityChange $first
		 */
		$last = end( $changes );
		$first = reset( $changes );

		$minor = true;
		$bot = true;

		$ids = [];

		foreach ( $changes as $change ) {
			$ids[] = $change->getId();
			$meta = $change->getMetadata();

			$minor = $minor && isset( $meta['minor'] ) && (bool)$meta['minor'];
			$bot = $bot && isset( $meta['bot'] ) && (bool)$meta['bot'];
		}

		$lastmeta = $last->getMetadata();
		$firstmeta = $first->getMetadata();

		$parentRevId = $firstmeta['parent_id'];
		$latestRevId = $lastmeta['rev_id'];

		$entityRev = $this->entityRevisionLookup->getEntityRevision(
			$entityId,
			$latestRevId,
			LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK
		);

		if ( !$entityRev ) {
			throw new MWException( "Failed to load revision $latestRevId of $entityId" );
		}

		$parentRev = $parentRevId ? $this->entityRevisionLookup->getEntityRevision( $entityId, $parentRevId ) : null;

		//XXX: we could avoid loading the entity data by merging the diffs programatically
		//     instead of re-calculating.
		$change = $this->changeFactory->newFromUpdate(
			$parentRev ? EntityChange::UPDATE : EntityChange::ADD,
			$parentRev ? $parentRev->getEntity() : null,
			$entityRev->getEntity()
		);

		$change->setFields(
			[
				ChangeRow::REVISION_ID => $last->getField( ChangeRow::REVISION_ID ),
				ChangeRow::USER_ID => $last->getUserId(),
				ChangeRow::TIME => $last->getTime(),
			]
		);

		$change->setMetadata( array_merge(
			$lastmeta,
			[
				'parent_id' => $parentRevId,
				'minor' => $minor,
				'bot' => $bot,
			]
		//FIXME: size before & size after
		//FIXME: size before & size after
		) );

		$info = $change->getInfo();
		$info['change-ids'] = $ids;
		$info['changes'] = $changes;
		$change->setField( ChangeRow::INFO, $info );

		return $change;
	}

	/**
	 * @param DiffOp $siteLinkDiffOp
	 *
	 * @return bool
	 */
	private function isBadgesOnlyChange( DiffOp $siteLinkDiffOp ) {
		return $siteLinkDiffOp instanceof Diff && !$siteLinkDiffOp->offsetExists( 'name' );
	}

	/**
	 * Coalesce consecutive changes by the same user to the same entity into one.
	 *
	 * A run of changes may be broken if the action performed changes (e.g. deletion
	 * instead of update) or if a sitelink pointing to the local wiki was modified.
	 *
	 * Some types of actions, like deletion, will break runs.
	 *
	 * @param EntityId $entityId
	 * @param EntityChange[] $changes
	 *
	 * @return Change[] grouped changes
	 */
	private function coalesceRuns( EntityId $entityId, array $changes ) {
		$coalesced = [];

		$currentRun = [];
		$currentUser = null;
		$currentAction = null;
		$breakNext = false;

		foreach ( $changes as $change ) {
			try {
				$action = $change->getAction();
				$meta = $change->getMetadata();
				$user = $meta['user_text'];

				$break = $breakNext
					|| $currentAction !== $action
					|| $currentUser !== $user;

				$breakNext = false;

				if ( !$break && ( $change instanceof ItemChange ) ) {
					$siteLinkDiff = $change->getSiteLinkDiff();
					if ( isset( $siteLinkDiff[$this->localSiteId] )
						&& !$this->isBadgesOnlyChange( $siteLinkDiff[$this->localSiteId] ) ) {
						$break = true;
						$breakNext = true;
					}
				}

				if ( $break ) {
					if ( !empty( $currentRun ) ) {
						try {
							$coalesced[] = $this->mergeChanges( $entityId, $currentRun );
						} catch ( MWException $ex ) {
							// Something went wrong while trying to merge the changes.
							// Just keep the original run.
							wfWarn( $ex->getMessage() );
							$coalesced = array_merge( $coalesced, $currentRun );
						}
					}

					$currentRun = [];
					$currentUser = $user;
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
				$coalesced[] = $this->mergeChanges( $entityId, $currentRun );
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
