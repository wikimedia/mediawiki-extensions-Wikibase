<?php

namespace Wikibase;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this interface which then takes care of
 * notifying all handlers.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeHandler {

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
	 * @var EntityLookup $entityLookup
	 */
	protected $entityLookup;

	/**
	 * @var string $siteGlobalId
	 */
	protected $siteGlobalId;

	public function __construct( EntityLookup $entityLookup = null, $siteGlobalId = null ) {
		if ( !$entityLookup ) {
			$entityLookup = ClientStoreFactory::getStore()->newEntityLookup();
		}

		if ( !$siteGlobalId ) {
			$siteGlobalId = Settings::get( 'siteGlobalID' );
		}

		$this->entityLookup = $entityLookup;
		$this->siteGlobalId = $siteGlobalId;
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
		$groups = array();

		foreach ( $changes as $change ) {
			$id = $change->getEntityId()->getPrefixedId();

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
	 * @since 0.4
	 *
	 * @param EntityChange[] $changes The changes to combine.
	 * @return Change a combined change representing the activity from all the original changes.
	 */
	public function mergeChanges( array $changes ) {
		if ( empty( $changes ) )  {
			return null;
		} else if ( count( $changes ) === 1 )  {
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

		foreach ( $changes as $change ) {
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
		$parent = $parentRevId ? $this->entityLookup->getEntity( $entityId, $parentRevId ) : null;

		if ( !$entity ) {
			throw new \MWException( "Failed to load revision $latestRevId of $entityId" );
		}

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
	 * @since 0.4
	 *
	 * @param EntityChange[] $changes
	 * @return EntityChange[] grouped changes
	 */
	public function coalesceRuns( array $changes ) {
		$coalesced = array();

		$currentRun = array();
		$currentUser = null;
		$currentEntity = null;
		$currentAction = null;
		$breakNext = false;

		foreach ( $changes as $change ) {
			$action = $change->getAction();
			$meta = $change->getMetadata();
			$user = $meta['user_text'];
			$entityId = $change->getEntityId()->getPrefixedId();

			$break = $breakNext
				|| $currentAction !== $action
				|| $currentUser !== $user
				|| $currentEntity !== $entityId;

			$breakNext = false;

			if ( !$break && ( $change instanceof ItemChange ) ) {
				$siteLinkDiff = $change->getSiteLinkDiff();
				if ( isset( $siteLinkDiff[ $this->siteGlobalId ] ) ) {
					$break = true;
					$breakNext = true;
				};
			}

			// FIXME: We should call changeNeedsRendering() and see if the needs-rendering
			//        stays the same, and break the run if not. This way, uninteresting
			//        changes can be sorted out more cleanely later.
			// FIXME: Perhaps more easily, get rid of them here and now!
			if ( $break ) {
				if ( !empty( $currentRun ) ) {
					$coalesced[] = $this->mergeChanges( $currentRun );
				}

				$currentRun = array();
				$currentUser = $user;
				$currentEntity = $entityId;
				$currentAction = $action === EntityChange::ADD ? EntityChange::UPDATE : $action;
			}

			$currentRun[] = $change;
		}

		if ( !empty( $currentRun ) ) {
			$coalesced[] = $this->mergeChanges( $currentRun );
		}

		foreach ( $coalesced as $change ) {
			print "\t$change\n";
		}

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
		$coalesced = array();

		$changesByEntity = $this->groupChangesByEntity( $changes );
		foreach ( $changesByEntity as $entityChanges ) {
			$entityChanges = $this->coalesceRuns( $entityChanges );
			$coalesced = array_merge( $coalesced, $entityChanges );
		}

		usort( $coalesced, 'Wikibase\ChangeHandler::compareChangesByTimestamp' );
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
		} else if ( $a->getTime() < $b->getTime() ) {
			return -1;
		}

		if ( $a->getId() > $b->getId() ) {
			return 1;
		} else if ( $a->getId() < $b->getId() ) {
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
		$changes = $this->coalesceChanges( $changes );

		wfRunHooks( 'WikibasePollBeforeHandle', array( $changes ) );

		foreach ( $changes as $change ) {
			wfRunHooks( 'WikibasePollHandle', array( $change ) );
		}

		wfRunHooks( 'WikibasePollAfterHandle', array( $changes ) );
	}

}