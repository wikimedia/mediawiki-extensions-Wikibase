<?php

namespace Wikibase\Store\Sql;

use DatabaseBase;
use DBUnexpectedError;
use Exception;
use LoadBalancer;
use MWException;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikibase\Store\ChangeDispatchCoordinator;
use Wikimedia\Assert\Assert;

/**
 * SQL based implementation of ChangeDispatchCoordinator;
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlChangeDispatchCoordinator implements ChangeDispatchCoordinator {

	/**
	 * @var callable Override for the array_rand function
	 */
	private $array_rand = 'array_rand';

	/**
	 * @var callable Override for the time function
	 */
	private $time = 'time';

	/**
	 * @var callable Override for $db->lock
	 */
	private $engageClientLockOverride = null;

	/**
	 * @var callable Override for $db->unlock
	 */
	private $releaseClientLockOverride = null;

	/**
	 * @var callable Override for !$db->lockIsFree
	 */
	private $isClientLockUsedOverride = null;

	/**
	 * @var int The number of changes to pass to a client wiki at once.
	 */
	private $batchSize = 1000;

	/**
	 * @var int Number of seconds to wait before dispatching to the same wiki again.
	 *           This affects the effective batch size, and this influences how changes
	 *           can be coalesced.
	 */
	private $dispatchInterval = 60;

	/**
	 * @var int Number of seconds to wait before testing a lock. Any target with a lock
	 *           timestamp newer than this will not be considered for selection.
	 */
	private $lockGraceInterval = 60;

	/**
	 * @var int Number of target wikis to select as a base set for random selection.
	 *           Setting this to 1 causes strict "oldest first" behavior, with the possibility
	 *           of grind/starvation if dispatching to the oldest wiki fails.
	 *           Setting this equal to (or greater than) the number of target wikis
	 *           causes a completely random selection of the target, regardless of when it
	 *           was last selected for dispatch.
	 */
	private $randomness = 10;

	/**
	 * @var string The name of the database table used to record state.
	 */
	private $stateTable = 'wb_changes_dispatch';

	/**
	 * @todo This shouldn't be here.
	 * @var string Name of the changes table.
	 */
	private $changesTable = 'wb_changes';

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var string|false The logical name of the repository's database
	 */
	private $repoDB;

	/**
	 * @var string The repo's global wiki ID
	 */
	private $repoSiteId;

	/**
	 * @param string|false $repoDB
	 * @param string $repoSiteId The repo's global wiki ID
	 */
	public function __construct( $repoDB, $repoSiteId ) {
		Assert::parameterType( 'string|boolean', $repoDB, '$repoDB' );

		$this->repoDB = $repoDB;
		$this->repoSiteId = $repoSiteId;

		$this->messageReporter = new NullMessageReporter();
	}

	/**
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * Sets the number of changes we would prefer to process in one go.
	 * Clients that are lagged by fewer changes than this may be skipped by selectClient().
	 *
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * @return MessageReporter
	 */
	public function getMessageReporter() {
		return $this->messageReporter;
	}

	/**
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @return int
	 */
	public function getRandomness() {
		return $this->randomness;
	}

	/**
	 * Sets the randomness level: selectClient() will randomly pick one of the $randomness
	 * most lagged eligible client wikis.
	 *
	 * @param int $randomness
	 */
	public function setRandomness( $randomness ) {
		$this->randomness = $randomness;
	}

	/**
	 * @return int
	 */
	public function getLockGraceInterval() {
		return $this->lockGraceInterval;
	}

	/**
	 * Sets the number of seconds after a lock should be challenged. This should be at least twice
	 * as long as we expect a dispatch pass for a single wiki to take. Challenging locks after a
	 * while safeguards against starving clients that were locked but never unlocked by a process
	 * that has since died.
	 *
	 * @param int $lockGraceInterval
	 */
	public function setLockGraceInterval( $lockGraceInterval ) {
		$this->lockGraceInterval = $lockGraceInterval;
	}

	/**
	 * @return int
	 */
	public function getDispatchInterval() {
		return $this->dispatchInterval;
	}

	/**
	 * Sets the number of seconds we would prefer to let a client "rest" before dispatching
	 * to it again. Clients that have received updates less than $dispatchInterval seconds ago
	 * may be skipped by selectClient().
	 *
	 * @param int $dispatchInterval
	 */
	public function setDispatchInterval( $dispatchInterval ) {
		$this->dispatchInterval = $dispatchInterval;
	}

	/**
	 * @return callable
	 */
	public function getArrayRandOverride() {
		return $this->array_rand;
	}

	/**
	 * Set override for array_rand(), for testing.
	 *
	 * @param callable $array_rand
	 */
	public function setArrayRandOverride( $array_rand ) {
		$this->array_rand = $array_rand;
	}

	/**
	 * @return callable
	 */
	public function getTimeOverride() {
		return $this->time;
	}

	/**
	 * Set override for time(), for testing.
	 *
	 * @param callable $time
	 */
	public function setTimeOverride( $time ) {
		$this->time = $time;
	}

	/**
	 * @return callable
	 */
	public function getEngageClientLockOverride() {
		return $this->engageClientLockOverride;
	}

	/**
	 * Set override for $db->lock, for testing.
	 *
	 * @param callable $engageClientLockOverride
	 */
	public function setEngageClientLockOverride( $engageClientLockOverride ) {
		$this->engageClientLockOverride = $engageClientLockOverride;
	}

	/**
	 * @return callable
	 */
	public function getIsClientLockUsedOverride() {
		return $this->isClientLockUsedOverride;
	}

	/**
	 * Set override for !$db->lockIsFree, for testing.
	 *
	 * @param callable $isClientLockUsedOverride
	 */
	public function setIsClientLockUsedOverride( $isClientLockUsedOverride ) {
		$this->isClientLockUsedOverride = $isClientLockUsedOverride;
	}

	/**
	 * @return callable
	 */
	public function getReleaseClientLockOverride() {
		return $this->releaseClientLockOverride;
	}

	/**
	 * Set override for $db->unlock, for testing.
	 *
	 * @param callable $releaseClientLockOverride
	 */
	public function setReleaseClientLockOverride( $releaseClientLockOverride ) {
		$this->releaseClientLockOverride = $releaseClientLockOverride;
	}

	/**
	 * @return LoadBalancer the repo's database load balancer.
	 */
	private function getRepoLB() {
		return wfGetLB( $this->repoDB );
	}

	/**
	 * @return DatabaseBase A connection to the repo's master database
	 */
	private function getRepoMaster() {
		return $this->getRepoLB()->getConnection( DB_MASTER, [], $this->repoDB );
	}

	/**
	 * @param DatabaseBase $db The repo database connection to release for re-use.
	 */
	private function releaseRepoMaster( DatabaseBase $db ) {
		$this->getRepoLB()->reuseConnection( $db );
	}

	/**
	 * Selects a client wiki and locks it. If no suitable client wiki can be found,
	 * this method returns null.
	 *
	 * Note: this implementation will try a wiki from the list returned by getCandidateClients()
	 * at random. If all have been tried and failed, it returns null.
	 *
	 * @return array An associative array containing the state of the selected client wiki
	 *               (or null, if no target could be locked). Fields are:
	 *
	 * * chd_site:     the client wiki's global site ID
	 * * chd_db:       the client wiki's logical database name
	 * * chd_seen:     the last change ID processed for that client wiki
	 * * chd_touched:  timestamp giving the last time that client wiki was updated
	 * * chd_lock:     the name of a global lock currently active for that client wiki
	 *
	 * @throws MWException if no available client wiki could be found.
	 *
	 * @see releaseWiki()
	 */
	public function selectClient() {
		$candidates = $this->getCandidateClients();

		while ( $candidates ) {
			// pick one
			$k = call_user_func( $this->array_rand, $candidates );
			$wiki = $candidates[ $k ];
			unset( $candidates[$k] );

			// lock it
			$state = $this->lockClient( $wiki );

			if ( $state ) {
				// got one
				return $state;
			}

			wfDebugLog( __METHOD__, 'Failed to grab dispatch lock for ' . $wiki );
			// try again
		}

		// we ran out of candidates
		wfDebugLog( __METHOD__, 'Could not lock any of the candidate client wikis for dispatching' );
		return null;
	}

	/**
	 * @return int The current time as a timestamp, in seconds since Epoch.
	 */
	private function now() {
		return call_user_func( $this->time );
	}

	/**
	 * Returns a list of possible client for the next pass.
	 * If no suitable clients are found, the resulting list will be empty.
	 *
	 * @return array
	 *
	 * @see selectClient()
	 */
	private function getCandidateClients() {
		$db = $this->getRepoMaster();

		// XXX: subject to clock skew. Use DB based "now" time?
		$freshDispatchTime = wfTimestamp( TS_MW, $this->now() - $this->dispatchInterval );
		$staleLockTime = wfTimestamp( TS_MW, $this->now() - $this->lockGraceInterval );

		// TODO: pass the max change ID as a parameter!
		$row = $db->selectRow(
			$this->changesTable,
			'max( change_id ) as maxid',
			[],
			__METHOD__ );

		$maxId = $row ? $row->maxid : 0;

		// Select all clients that:
		//   have not been touched for $dispatchInterval seconds
		//      ( or are lagging by more changes than given by batchSize )
		//   and are not locked
		//      ( or the lock is older than $lockGraceInterval ).
		//   and have not seen all changes
		//   and are not disabled
		// Limit the list to $randomness items. Candidates will be picked
		// from the resulting list at random.

		$candidates = $db->selectFieldValues(
			$this->stateTable,
			'chd_site',
			array( '( chd_lock is NULL ' . // not locked or...
					' OR chd_touched < ' . $db->addQuotes( $staleLockTime ) . ' ) ', // ...the lock is old
				'( chd_touched < ' . $db->addQuotes( $freshDispatchTime ) . // and wasn't touched too recently or...
					' OR ( ' . (int)$maxId. ' - chd_seen ) > ' . (int)$this->batchSize . ') ' , // or it's lagging by more changes than batchSite
				'chd_seen < ' . (int)$maxId, // and not fully up to date.
				'chd_disabled = 0' // and not disabled
			),
			__METHOD__,
			array(
				'ORDER BY' => 'chd_seen ASC',
				'LIMIT' => (int)$this->randomness
			)
		);

		return $candidates;
	}

	/**
	 * Initializes the dispatch table by injecting dummy records for all target wikis
	 * that are in the configuration but not yet in the dispatch table.
	 *
	 * @param string[] $clientWikiDBs Associative array mapping client wiki IDs to
	 * client wiki (logical) database names.
	 *
	 * @throws DBUnexpectedError
	 */
	public function initState( array $clientWikiDBs ) {
		$db = $this->getRepoMaster();

		$trackedSiteIds = $db->selectFieldValues(
			$this->stateTable,
			'chd_site',
			[],
			__METHOD__
		);

		$untracked = array_diff_key( $clientWikiDBs, array_flip( $trackedSiteIds ) );

		foreach ( $untracked as $siteID => $wikiDB ) {
			$state = array(
				'chd_site' => $siteID,
				'chd_db' => $wikiDB,
				'chd_seen' => 0,
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => 0,
			);

			$db->insert(
				$this->stateTable,
				$state,
				__METHOD__,
				array( 'IGNORE' )
			);

			$this->log( "Initialized dispatch state for $siteID" );
		}

		$this->releaseRepoMaster( $db );
	}

	/**
	 * Attempt to lock the given target wiki. If it can't be locked because
	 * another dispatch process is working on it, this method returns false.
	 *
	 * @param string $siteID The ID of the client wiki to lock.
	 *
	 * @throws MWException if there are no client wikis to chose from.
	 * @throws Exception
	 * @return array An associative array containing the state of the selected client wiki
	 *               (see selectClient()) or false if the client wiki could not be locked.
	 *
	 * @see selectClient()
	 */
	public function lockClient( $siteID ) {
		$this->trace( "Trying $siteID" );

		// start transaction
		$db = $this->getRepoMaster();
		$db->begin( __METHOD__ );

		try {
			$this->trace( 'Loaded repo db master' );

			// get client state
			$state = $db->selectRow(
				$this->stateTable,
				array( 'chd_site', 'chd_db', 'chd_seen', 'chd_touched', 'chd_lock', 'chd_disabled' ),
				array( 'chd_site' => $siteID ),
				__METHOD__,
				array( 'FOR UPDATE' )
			);

			$this->trace( "Loaded dispatch changes row for $siteID" );

			if ( !$state ) {
				$this->warn( "ERROR: $siteID is not in the dispatch table." );
				return false;
			} else {
				$this->trace( "Loading state for $siteID" );
				// turn the row object into an array
				$state = get_object_vars( $state );
			}

			$lock = $this->getClientLockName( $siteID );

			if ( $state['chd_lock'] !== null ) {
				// bail out if another dispatcher instance is holding a lock for that wiki
				if ( $this->isClientLockUsed( $db, $lock ) ) {
					$this->trace( "$siteID is already being handled by another process."
								. " (lock: " . $state['chd_lock'] . ")" );

					$db->rollback( __METHOD__ );
					$this->releaseRepoMaster( $db );
					return false;
				}
			}

			$ok = $this->engageClientLock( $db, $lock );

			if ( !$ok ) {
				// This really shouldn't happen, since we already checked if another process has a lock.
				// The write lock we are holding on the wb_changes_dispatch table should be preventing
				// any race conditions.
				// However, another process may still hold the lock if it grabbed it without locking
				// wb_changes_dispatch, or if it didn't record the lock in wb_changes_dispatch.

				$this->trace( "Warning: Failed to acquire lock $lock for site $siteID!" );

				$db->rollback( __METHOD__ );
				$this->releaseRepoMaster( $db );
				return false;
			}

			$this->trace( "Locked client $siteID with $lock" );

			$state['chd_lock'] = $lock;
			$state['chd_touched'] = wfTimestamp( TS_MW, $this->now() ); // XXX: use DB time

			// update state record for already known client wiki
			$db->update(
				$this->stateTable,
				$state,
				array( 'chd_site' => $state['chd_site'] ),
				__METHOD__
			);
		} catch ( Exception $ex ) {
			$db->rollback( __METHOD__ );
			$this->releaseRepoMaster( $db );
			throw $ex;
		}

		$db->commit( __METHOD__ );
		$this->releaseRepoMaster( $db );

		$this->trace( "Locked site $siteID at {$state['chd_seen']}." );

		unset( $state['chd_disabled'] ); // don't mess with this.

		return $state;
	}

	/**
	 * Updates the given client wiki's entry in the dispatch table and
	 * releases the global lock on that wiki.
	 *
	 * @param array $state Associative array representing the client wiki's state before the
	 *                      update pass, as returned by selectWiki().
	 *
	 * @throws Exception
	 * @see selectWiki()
	 */
	public function releaseClient( array $state ) {
		$siteID = $state['chd_site'];
		$wikiDB = $state['chd_db'];

		// start transaction
		$db = $this->getRepoMaster();
		$db->begin( __METHOD__ );

		try {
			$this->releaseClientLock( $db, $state['chd_lock'] );

			$state['chd_lock'] = null;
			$state['chd_touched'] = wfTimestamp( TS_MW, $this->now() );
			//XXX: use the DB's time to avoid clock skew?

			// insert state record with the new state.
			$db->update(
				$this->stateTable,
				$state,
				array( 'chd_site' => $state['chd_site'] ),
				__METHOD__
			);
		} catch ( Exception $ex ) {
			$db->rollback( __METHOD__ );
			$this->releaseRepoMaster( $db );
			throw $ex;
		}

		$db->commit( __METHOD__ );
		$this->releaseRepoMaster( $db );

		$this->trace( "Released $wikiDB for site $siteID at {$state['chd_seen']}." );
	}

	/**
	 * Determines the name of the global lock that should be used to lock the given client.
	 *
	 * @param string $siteID The site ID of the wiki to lock
	 *
	 * @return string the lock name to use.
	 */
	private function getClientLockName( $siteID ) {
		// NOTE: Lock names are global, not scoped per database. To avoid clashes,
		// we need to include both the ID of the repo and the ID of the client.
		$name = "Wikibase.{$this->repoSiteId}.dispatchChanges.$siteID";
		return str_replace( ' ', '_', $name );
	}

	/**
	 * Tries to acquire a global lock on the given client wiki.
	 *
	 * @param DatabaseBase $db The database connection to work on.
	 * @param string  $lock  The name of the lock to release.
	 *
	 * @return bool whether the lock was engaged successfully.
	 */
	private function engageClientLock( DatabaseBase $db, $lock ) {
		if ( isset( $this->engageClientLockOverride ) ) {
			return call_user_func( $this->engageClientLockOverride, $db, $lock );
		}

		return $db->lock( $lock, __METHOD__ );
	}

	/**
	 * Releases the given global lock on the given client wiki.
	 *
	 * @param DatabaseBase $db The database connection to work on.
	 * @param string  $lock  The name of the lock to release.
	 *
	 * @return bool whether the lock was released successfully.
	 */
	private function releaseClientLock( DatabaseBase $db, $lock ) {
		if ( isset( $this->releaseClientLockOverride ) ) {
			return call_user_func( $this->releaseClientLockOverride, $db, $lock );
		}

		return $db->unlock( $lock, __METHOD__ );
	}

	/**
	 * Checks the given global lock on the given client wiki.
	 *
	 * @param DatabaseBase $db The database connection to work on.
	 * @param string  $lock  The name of the lock to check.
	 *
	 * @return bool true if the given lock is currently held by another process, false otherwise.
	 */
	private function isClientLockUsed( DatabaseBase $db, $lock ) {
		if ( isset( $this->isClientLockUsedOverride ) ) {
			return call_user_func( $this->isClientLockUsedOverride, $db, $lock );
		}

		return !$db->lockIsFree( $lock, __METHOD__ );
	}

	private function warn( $message ) {
		wfLogWarning( $message );

		$this->messageReporter->reportMessage( $message );
	}

	private function log( $message ) {
		wfDebugLog( __CLASS__, $message );

		$this->messageReporter->reportMessage( $message );
	}

	private function trace( $message ) {
		wfDebugLog( __CLASS__, $message );
	}

}
