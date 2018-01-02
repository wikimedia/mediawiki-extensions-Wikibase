<?php

namespace Wikibase\Store\Sql;

use Exception;
use MWException;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikibase\Store\ChangeDispatchCoordinator;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * SQL based implementation of ChangeDispatchCoordinator;
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
	 * @var int Number of target wikis to select as a base set for random selection.
	 *           Setting this to 1 causes strict "oldest first" behavior, with the possibility
	 *           of grind/starvation if dispatching to the oldest wiki fails.
	 *           Setting this equal to (or greater than) the number of target wikis
	 *           causes a completely random selection of the target, regardless of when it
	 *           was last selected for dispatch.
	 */
	private $randomness = 15;

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
	 * @var LBFactory
	 */
	private $LBFactory;

	/**
	 * @param string|false $repoDB
	 * @param string $repoSiteId The repo's global wiki ID
	 * @param LBFactory $LBFactory
	 */
	public function __construct(
		$repoDB,
		$repoSiteId,
		LBFactory $LBFactory
	) {
		Assert::parameterType( 'string|boolean', $repoDB, '$repoDB' );

		$this->repoDB = $repoDB;
		$this->repoSiteId = $repoSiteId;

		$this->messageReporter = new NullMessageReporter();
		$this->LBFactory = $LBFactory;
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

	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
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
	 * Set override for array_rand(), for testing.
	 *
	 * @param callable $array_rand
	 */
	public function setArrayRandOverride( $array_rand ) {
		$this->array_rand = $array_rand;
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
	 * Set override for $db->lock, for testing.
	 *
	 * @param callable $engageClientLockOverride
	 */
	public function setEngageClientLockOverride( $engageClientLockOverride ) {
		$this->engageClientLockOverride = $engageClientLockOverride;
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
	 * Set override for $db->unlock, for testing.
	 *
	 * @param callable $releaseClientLockOverride
	 */
	public function setReleaseClientLockOverride( $releaseClientLockOverride ) {
		$this->releaseClientLockOverride = $releaseClientLockOverride;
	}

	/**
	 * @param string $stateTable
	 */
	public function setStateTable( $stateTable ) {
		$this->stateTable = $stateTable;
	}

	/**
	 * @param string $changesTable
	 */
	public function setChangesTable( $changesTable ) {
		$this->changesTable = $changesTable;
	}

	/**
	 * @return LoadBalancer the repo's database load balancer.
	 */
	private function getRepoLB() {
		return $this->LBFactory->getMainLB( $this->repoDB );
	}

	/**
	 * @return IDatabase A connection to the repo's master database
	 */
	private function getRepoMaster() {
		return $this->getRepoLB()->getConnection( DB_MASTER, [], $this->repoDB );
	}

	/**
	 * @return IDatabase A connection to the repo's replica database
	 */
	private function getRepoReplica() {
		return $this->getRepoLB()->getConnection( DB_REPLICA, [], $this->repoDB );
	}

	/**
	 * @param IDatabase $db The repo database connection to release for re-use.
	 */
	private function releaseRepoDb( IDatabase $db ) {
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
		$dbr = $this->getRepoReplica();

		// XXX: subject to clock skew. Use DB based "now" time?
		$freshDispatchTime = wfTimestamp( TS_MW, $this->now() - $this->dispatchInterval );

		// TODO: pass the max change ID as a parameter!
		$row = $dbr->selectRow(
			$this->changesTable,
			'max( change_id ) as maxid',
			[],
			__METHOD__ );

		$maxId = $row ? $row->maxid : 0;

		// Select all clients that:
		//   have not been touched for $dispatchInterval seconds
		//      ( or are lagging by more changes than given by batchSize )
		//   and have not seen all changes
		//   and are not disabled
		// Limit the list to $randomness items. Candidates will be picked
		// from the resulting list at random.

		$where = [
			'( chd_touched < ' . $dbr->addQuotes( $freshDispatchTime ) . // and wasn't touched too recently or
				' OR ( ' . (int)$maxId. ' - chd_seen ) > ' . (int)$this->batchSize . ') ' , // or it's lagging by more changes than batchSite
			'chd_seen < ' . (int)$maxId, // and not fully up to date.
			'chd_disabled = 0' // and not disabled
		];

		$candidates = $dbr->selectFieldValues(
			$this->stateTable,
			'chd_site',
			$where,
			__METHOD__,
			[
				'ORDER BY' => 'chd_seen ASC',
				'LIMIT' => (int)$this->randomness
			]
		);

		$this->releaseRepoDb( $dbr );
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
		$dbr = $this->getRepoReplica();

		$trackedSiteIds = $dbr->selectFieldValues(
			$this->stateTable,
			'chd_site',
			[],
			__METHOD__
		);

		$untracked = array_diff_key( $clientWikiDBs, array_flip( $trackedSiteIds ) );

		if ( empty( $untracked ) ) {
			$this->releaseRepoDb( $dbr );
			return;
		}

		$dbw = $this->getRepoMaster();
		foreach ( $untracked as $siteID => $wikiDB ) {
			$state = [
				'chd_site' => $siteID,
				'chd_db' => $wikiDB,
				'chd_seen' => 0,
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => 0,
			];

			$dbw->insert(
				$this->stateTable,
				$state,
				__METHOD__,
				[ 'IGNORE' ]
			);

			$this->log( "Initialized dispatch state for $siteID" );
		}

		$this->releaseRepoDb( $dbr );
		$this->releaseRepoDb( $dbw );
	}

	/**
	 * Attempt to lock the given target wiki. If it can't be locked because
	 * another dispatch process is working on it, this method returns false.
	 *
	 * @param string $siteID The ID of the client wiki to lock.
	 *
	 * @throws MWException if there are no client wikis to chose from.
	 * @throws Exception
	 * @return bool|array An associative array containing the state of the selected client wiki
	 *               (see selectClient()) or false if the client wiki could not be locked.
	 *
	 * @see selectClient()
	 */
	public function lockClient( $siteID ) {
		$this->trace( "Trying $siteID" );

		$dbr = $this->getRepoReplica();

		try {
			$this->trace( 'Loaded repo db master' );

			// get client state
			$state = $dbr->selectRow(
				$this->stateTable,
				[ 'chd_site', 'chd_db', 'chd_seen', 'chd_touched', 'chd_lock', 'chd_disabled' ],
				[ 'chd_site' => $siteID ],
				__METHOD__
			);

			$this->releaseRepoDb( $dbr );

			if ( !$state ) {
				$this->warn( "ERROR: $siteID is not in the dispatch table." );
				return false;
			} else {
				$this->trace( "Loading state for $siteID" );
				// turn the row object into an array
				$state = get_object_vars( $state );
			}

			$lock = $this->getClientLockName( $siteID );
			$ok = $this->engageClientLock( $lock );

			if ( !$ok ) {
				// This really shouldn't happen, since we already checked if another process has a lock.
				// The write lock we are holding on the wb_changes_dispatch table should be preventing
				// any race conditions.
				// However, another process may still hold the lock if it grabbed it without locking
				// wb_changes_dispatch, or if it didn't record the lock in wb_changes_dispatch.

				$this->trace( "Warning: Failed to acquire lock $lock for site $siteID!" );

				return false;
			}
		} catch ( Exception $ex ) {
			$this->releaseRepoDb( $dbr );
			throw $ex;
		}

		$this->trace( "Loaded dispatch changes row for $siteID" );

		$this->trace( "Locked client $siteID with $lock" );

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
				[ 'chd_site' => $state['chd_site'] ],
				__METHOD__
			);
		} catch ( Exception $ex ) {
			$db->rollback( __METHOD__ );
			$this->releaseRepoDb( $db );
			throw $ex;
		}
		$db->commit( __METHOD__ );

		// Wait for all database replicas to be updated, but only for repo db. The
		// "domain" argument is documented at ILBFactory::waitForReplication.
		$this->LBFactory->waitForReplication( [ 'domain' => $this->repoDB ] );

		$this->releaseRepoDb( $db );

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
	 * @param string  $lock  The name of the lock to engage.
	 *
	 * @return bool whether the lock was engaged successfully.
	 */
	protected function engageClientLock( $lock ) {
		$dbw = $this->getRepoMaster();

		if ( isset( $this->engageClientLockOverride ) ) {
			$success = call_user_func( $this->engageClientLockOverride, $dbw, $lock );
		} else {
			$success = $dbw->lock( $lock, __METHOD__ );
		}

		$this->releaseRepoDb( $dbw );

		return $success;
	}

	/**
	 * Releases the given global lock on the given client wiki.
	 *
	 * @param IDatabase $db The database connection to work on.
	 * @param string  $lock  The name of the lock to release.
	 *
	 * @return bool whether the lock was released successfully.
	 */
	protected function releaseClientLock( IDatabase $db, $lock ) {
		if ( isset( $this->releaseClientLockOverride ) ) {
			return call_user_func( $this->releaseClientLockOverride, $db, $lock );
		}

		return $db->unlock( $lock, __METHOD__ );
	}

	/**
	 * Checks the given global lock on the given client wiki.
	 *
	 * @param IDatabase $db The database connection to work on.
	 * @param string  $lock  The name of the lock to check.
	 *
	 * @return bool true if the given lock is currently held by another process, false otherwise.
	 */
	protected function isClientLockUsed( IDatabase $db, $lock ) {
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
