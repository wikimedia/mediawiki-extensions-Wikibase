<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that polls for Wikibase changes in the shared wb_changes table
 * and dispatches the relevant changes to any client wikis' job queues.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchChanges extends \Maintenance {

	/**
	 * @var string: the name of the database table used to record state.
	 */
	protected $stateTable;

	/**
	 * @var ChangesTable: access to the changes table
	 */
	protected $changesTable;

	/**
	 * @var ChunkCache: cache for changes
	 */
	protected $changesCache;

	/**
	 * @var string: the logical name of the repository's database
	 */
	protected $repoDB;

	/**
	 * @var array: Logical names of local client wiki databases, provided as a mapping of
	 *             global site ID to database name for each client wiki.
	 */
	protected $clientWikis;

	/**
	 * @var int: the number of changes to pass to a client wiki at once.
	 */
	protected $batchSize;

	/**
	 * @var int: factor used to compute the number of changes to load from the changes table at once
	 *           based on $this->batchSize.
	 */
	protected $batchChunkFactor;

	/**
	 * @var int: factor used to compute the maximum size of the chunk cache. The total cache size is
	 *           $this->batchSize * $this->batchChunkFactor * $this->batchCacheFactor
	 */
	protected $batchCacheFactor;

	/**
	 * @var int: the number of client update passes to perform before exiting.
	 */
	protected $maxPasses;

	/**
	 * @var int: the number of seconds passes to run before exiting.
	 */
	protected $maxTime;

	/**
	 * @var int: the number of seconds to wait before executing the next pass.
	 */
	protected $delay;

	/**
	 * @var int: Number of seconds to wait before dispatching to the same wiki again.
	 *           This affects the effective batch size, and this influences how changes
	 *           can be coalesced.
	 */
	protected $dispatchInterval;

	/**
	 * @var int: Number of seconds to wait before testing a lock. Any target with a lock
	 *           timestamp newer than this will not be considered for selection.
	 */
	protected $lockGraceInterval;

	/**
	 * @var int: Number of target wikis to select as a base set for random selection.
	 *           Setting this to 1 causes strict "oldest first" behavior, with the possibility
	 *           of grind/starvation if dispatching to the oldest wiki fails.
	 *           Setting this equal to (or greater than) the number of target wikis
	 *           causes a completely random selection of the target, regardless of when it
	 *           was last selected for dispatch.
	 */
	protected $randomness = 5;

	/**
	 * @var bool: whether output should be version.
	 */
	protected $verbose;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->mDescription =
			'Maintenance script that polls for Wikibase changes in the shared wb_changes table
			and dispatches them to any client wikis using their job queue.';

		$this->addOption( 'verbose', "Report activity." );
		$this->addOption( 'idle-delay', "Seconds to sleep when idle. Default: 10", false, true );
		$this->addOption( 'dispatch-interval', "How often to dispatch to each target wiki. "
					. "Default: every 60 seconds", false, true );
		$this->addOption( 'lock-grace-interval', "Seconds after wich to probe for orphaned locks. "
					. "Default: 60", false, true );
		$this->addOption( 'randomness', "Number of least current target wikis to pick from at random. "
					. "Default: 5.", false, true );
		$this->addOption( 'max-passes', "The number of passes to perform. "
					. "Default: 1 if --max-time is not set, infinite if it is.", false, true );
		$this->addOption( 'max-time', "The number of seconds to run before exiting, "
					. "if --max-passes is not reached. Default: infinite.", false, true );
		$this->addOption( 'batch-size', "Max number of changes to pass to a client at a time. "
					. "Default: 1000", false, true );
	}

	/**
	 * Initializes members from command line options and configuration settings.
	 */
	protected function init() {
		$this->stateTable = 'wb_changes_dispatch'; //TODO: allow alternate table name for testing
		$this->changesTable = new ChangesTable(); //TODO: allow injection of a mock instance for testing

		$this->repoDB = Settings::get( 'changesDatabase' );
		$this->clientWikis = Settings::get( 'localClientDatabases' );
		$this->batchChunkFactor = Settings::get( 'dispatchBatchChunkFactor' );
		$this->batchCacheFactor = Settings::get( 'dispatchBatchCacheFactor' );

		$this->batchSize = intval( $this->getOption( 'batch-size', 1000 ) );
		$this->maxTime = intval( $this->getOption( 'max-time', PHP_INT_MAX ) );
		$this->maxPasses = intval( $this->getOption( 'max-passes', $this->maxTime < PHP_INT_MAX ? PHP_INT_MAX : 1 ) );
		$this->delay = intval( $this->getOption( 'idle-delay', 10 ) );
		$this->dispatchInterval = intval( $this->getOption( 'dispatch-interval', 60 ) );
		$this->lockGraceInterval = intval( $this->getOption( 'lock-grace-interval', 60 ) );

		$this->verbose = $this->getOption( 'verbose', false );

		$cacheChunkSize = $this->batchSize * $this->batchChunkFactor;
		$cacheSize = $cacheChunkSize * $this->batchCacheFactor;
		$this->changesCache = new ChunkCache( $this->changesTable, $cacheChunkSize, $cacheSize );

		// make sure we have a mapping from siteId to database name in clientWikis:
		foreach ( $this->clientWikis as $siteID => $dbName ) {
			if ( is_int( $siteID ) ) {
				unset( $this->clientWikis[$siteID] );
				$this->clientWikis[$dbName] = $dbName;
			}
		}
	}

	/**
	 * @return \LoadBalancer the repo's database load balancer.
	 */
	protected function getRepoLB() {
		return wfGetLB( $this->repoDB );
	}

	/**
	 * @param  string|bool $wiki: the logical name of the client wiki's database.
	 *
	 * @return \LoadBalancer $wiki's database load balancer.
	 */
	protected function getClientLB( $wiki ) {
		return wfGetLB( $wiki );
	}

	/**
	 * @return \DatabaseBase A connection to the repo's master database
	 */
	protected function getRepoMaster() {
		return $this->getRepoLB()->getConnection( DB_MASTER, array(), $this->repoDB );
	}

	/**
	 * @param  string|bool $wiki: the logical name of the client wiki's database.
	 *
	 * @return \DatabaseBase A connection to $wiki's master database
	 */
	protected function getClientMaster( $wiki ) {
		return $this->getClientLB( $wiki )->getConnection( DB_MASTER, array(), $wiki );
	}

	/**
	 * @param \DatabaseBase $db: the repo database connection to release for re-use.
	 */
	protected function releaseRepoMaster( \DatabaseBase $db ) {
		$this->getRepoLB()->reuseConnection( $db );
	}

	/**
	 * @param  string|bool  $wiki: the logical name of the client wiki's database.
	 * @param \DatabaseBase $db: the client database connection to release for re-use.
	 */
	protected function releaseClientMaster( $wiki, \DatabaseBase $db  ) {
		$this->getClientLB( $wiki )->reuseConnection( $db );
	}

	/**
	 * Maintenance script entry point.
	 *
	 * This will run $this->runPass() in a loop, the number of times specified by $this->maxPasses.
	 * If $this->maxTime is exceeded before all passes are run, execution is also terminated.
	 * If no suitable target wiki can be found for a pass, we sleep for $this->delay seconds
	 * instead of dispatching.
	 */
	public function execute() {
		if ( !defined( 'WBL_VERSION' ) ) {
			// Since people might waste time debugging odd errors when they forget to enable the extension. BTDT.
			throw new \MWException( "WikibaseLib has not been loaded." );
		}

		$this->init();

		if ( empty( $this->clientWikis ) ) {
			throw new \MWException( "No client wikis configured! Please set \$wgWBRepoSettings['localClientDatabases']." );
		}

		$this->initStateTable();

		$passes = $this->maxPasses === PHP_INT_MAX ? "unlimited" : $this->maxPasses;
		$time = $this->maxTime === PHP_INT_MAX ? "unlimited" : $this->maxTime;

		$this->log( "Starting loop for $passes passes or $time seconds" );

		$startTime = time();
		$t = 0;

		// Run passes in a loop, sleeping when idle.
		// Note that idle passes need to be counted to avoid processes staying alive
		// for an indefinite time, potentially leading to a pile up when used with cron.
		for ( $c = 0; $c < $this->maxPasses; ) {
			if ( $t  > $this->maxTime ) {
				$this->trace( "Reached max time after $t seconds." );
				// timed out
				break;
			}

			$c++;

			try {
				$this->trace( "Picking a client wiki..." );
				$wikiState = $this->selectClient();

				if ( $wikiState ) {
					$this->dispatchTo( $wikiState  );
				} else {
					// Try again later, unless we have already reached the limit.
					if ( $c < $this->maxPasses ) {
						$this->trace( "Idle: No client wiki found in need of dispatching. "
							. "Sleeping for {$this->delay} seconds." );

						sleep( $this->delay );
					} else {
						$this->trace( "Idle: No client wiki found in need of dispatching. " );
					}
				}
			} catch ( \Exception $ex ) {
				if ( $c < $this->maxPasses ) {
					$this->log( "ERROR: $ex; sleeping for {$this->delay} seconds" );
					sleep( $this->delay );
				} else {
					$this->log( "ERROR: $ex" );
				}
			}

			$t = ( time() - $startTime );
		}

		$this->log( "Done, exiting after $c passes and $t seconds." );
	}

	/**
	 * Performs one update pass. This involves the following steps:
	 *
	 * 1) Get a batch of changes for the client wiki.
	 * 2) Post a notification job to the client wiki's job queue.
	 * 3) Update the dispatch log for the client wiki, and release it.
	 *
	 * @param array $wikiState the dispatch state of a client wiki, as returned by lockClient()
	 * @return int The number of changes dispatched
	 */
	public function dispatchTo( $wikiState ) {
		wfProfileIn( __METHOD__ );

		$wikiDB = $wikiState['chd_db'];
		$siteID = $wikiState['chd_site'];
		$after = intval( $wikiState['chd_seen'] );

		// get relevant changes
		$this->trace( "Finding pending changes for $wikiDB" );
		list( $changes, $continueAfter ) = $this->getPendingChanges( $siteID, $wikiDB, $after );

		$n = count( $changes );

		if ( $n > 0 ) {
			$this->trace( "Dispatching $n changes to $wikiDB, up to #$continueAfter" );

			// notify the client wiki about the changes
			$this->postChangeJobs( $siteID, $wikiDB, $changes );
		}

		$this->releaseClient( $continueAfter, $wikiState );

		if ( $n === 0 ) {
			$this->trace( "Posted no changes to $wikiDB (nothing to do). "
						. "Next ID is $continueAfter." );
		} else {
			/* @var Change $last */
			$last = end( $changes );

			$this->log( "Posted $n changes to $wikiDB, "
				. "up to ID " . $last->getId() . ", timestamp " . $last->getTime() . ". "
				. "Lag is " . $last->getAge() . " seconds. "
				. "Next ID is $continueAfter." );
		}

		wfProfileOut( __METHOD__ );
		return $n;
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
	 * @throws \MWException if no available client wiki could be found.
	 *
	 * @see releaseWiki()
	 */
	protected function selectClient() {
		wfProfileIn( __METHOD__ );

		$candidates = $this->getCandidateClients();

		while ( $candidates ) {
			// pick one
			$k = array_rand( $candidates );
			$wiki = $candidates[ $k ];
			unset( $candidates[$k] );

			// lock it
			$state = $this->lockClient( $wiki );

			if ( $state ) {
				// got one
				wfProfileOut( __METHOD__ );
				return $state;
			}

			// try again
		}

		// we ran out of candidates
		wfProfileOut( __METHOD__ );
		return null;
	}

	/**
	 * Returns a list of possible client for the next pass.
	 * If no suitable clients are found, the resulting list will be empty.
	 *
	 * @return array
	 *
	 * @see selectClient()
	 */
	protected function getCandidateClients() {
		wfProfileIn( __METHOD__ );
		$db = $this->getRepoMaster();

		// XXX: subject to clock skew. Use DB based "now" time?
		$freshDispatchTime = wfTimestamp( TS_MW, time() - $this->dispatchInterval );
		$staleLockTime = wfTimestamp( TS_MW, time() - $this->lockGraceInterval );

		$row = $db->selectRow(
			$this->changesTable->getName(),
			'max( change_id ) as maxid',
			array(),
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

		$res = $db->select(
			$this->stateTable,
			array( 'chd_site' ),
			array( '( chd_lock is NULL ' . // not locked or...
					' OR chd_touched < ' . $db->addQuotes( $staleLockTime ) . ' ) ', // ...the log is old
				'( chd_touched < ' . $db->addQuotes( $freshDispatchTime ) . // and wasn't touched too recently or...
					' OR ( ' . (int)$maxId. ' - chd_seen ) > ' . (int)$this->batchSize . ') ' , // or it's lagging by more changes than batchSite
				'chd_seen < ' . (int)$maxId, // and not fully up to day.
				'chd_disabled = 0' ) // and not disabled
			,
			__METHOD__,
			array(
				'ORDER BY chd_seen ASC',
				'FOR UPDATE',
				'LIMIT ' . (int)$this->randomness
			)
		);

		//TODO: also exclude targets with chd_seen = max( change_id )
		//TODO: also include targets with ( max( change_id ) - chd_seen ) > batch_size

		$candidates = array();
		while ( $row = $res->fetchRow() ) {
			$candidates[] = $row['chd_site'];
		}

		wfProfileOut( __METHOD__ );
		return $candidates;
	}

	/**
	 * Initializes the dispatch table by injecting dummy records for all target wikis
	 * that are in the configuration but not yet in the dispatch table.
	 */
	protected function initStateTable() {
		wfProfileIn( __METHOD__ );
		$db = $this->getRepoMaster();

		$res = $db->select( $this->stateTable,
			array( 'chd_site' ),
			array(),
			__METHOD__ );

		$tracked = array();

		while ( $row = $res->fetchRow() ) {
			$k = $row[ 'chd_site' ];
			$tracked[$k] = $k;
		}

		$untracked = array_diff_key( $this->clientWikis, $tracked );

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

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Attempt to lock the given target wiki. If it can't be locked because
	 * another dispatch process is working on it, this method returns false.
	 *
	 * @param string $siteID The ID of the client wiki to lock.
	 *
	 * @throws \MWException if there are no client wikis to chose from.
	 * @throws \Exception
	 * @return array An associative array containing the state of the selected client wiki
	 *               (see selectClient()) or false if the client wiki could not be locked.
	 *
	 * @see selectClient()
	 */
	protected function lockClient( $siteID ) {
		if ( !isset( $this->clientWikis[ $siteID ] ) ) {
			throw new \MWException( "Wiki not configured: $siteID; "
					."consider removing it from the " . $this->stateTable );
		}

		wfProfileIn( __METHOD__ );
		$wikiDB = $this->clientWikis[ $siteID ];

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
				$this->log( "ERROR: $siteID is not in the dispatch table." );

				wfProfileOut( __METHOD__ );
				return false;
			} else {
				$this->trace( "Loading state for $siteID" );
				// turn the row object into an array
				$state = get_object_vars( $state );
			}

			if ( $state['chd_lock'] !== null ) {

				// bail out if another dispatcher instance is holding a lock for that wiki
				if ( $this->isClientLockUsed( $wikiDB, $state['chd_lock'] ) ) {
					$this->trace( "$siteID is already being handled by another process."
								. " (lock: " . $state['chd_lock'] . ")" );

					$db->rollback( __METHOD__ );
					$this->releaseRepoMaster( $db );

					wfProfileOut( __METHOD__ );
					return false;
				}
			}

			$lock = $this->getClientLock( $wikiDB );

			if ( $lock === false ) {
				// This really shouldn't happen, since we already checked if another process has a lock.
				// The write lock we are holding on the wb_changes_dispatch table should be preventing
				// any race conditions.
				// However, another process may still hold the lock if it grabbed it without locking
				// wb_changes_dispatch, or if it didn't record the lock in wb_changes_dispatch.

				$this->log( "Warning: Failed to acquire lock on $wikiDB for site $siteID!" );

				$db->rollback( __METHOD__ );
				$this->releaseRepoMaster( $db );

				wfProfileOut( __METHOD__ );
				return false;
			}

			$this->trace( "Locked client $siteID" );

			$state['chd_lock'] = $lock;
			$state['chd_touched'] = wfTimestamp( TS_MW ); // XXX: use DB time

			// update state record for already known client wiki
			$db->update(
				$this->stateTable,
				$state,
				array( 'chd_site' => $state['chd_site'] ),
				__METHOD__
			);
		} catch ( \Exception $ex ) {
			$db->rollback( __METHOD__ );
			$this->releaseRepoMaster( $db );
			wfProfileOut( __METHOD__ );

			throw $ex;
		}

		$db->commit( __METHOD__ );
		$this->releaseRepoMaster( $db );

		$this->trace( "Locked $wikiDB for site $siteID at {$state['chd_seen']}." );

		unset( $state['chd_disabled'] ); // don't mess with this.

		wfProfileOut( __METHOD__ );
		return $state;
	}

	/**
	 * Updates the given client wiki's entry in the dispatch table and
	 * releases the global lock on that wiki.
	 *
	 * @param int   $seen   :  the ID of the last change processed in the pass.
	 * @param array $state  : associative array representing the client wiki's state before the
	 *                      update pass, as returned by selectWiki().
	 *
	 * @see selectWiki()
	 */
	protected function releaseClient( $seen, array $state ) {
		wfProfileIn( __METHOD__ );

		$siteID = $state['chd_site'];
		$wikiDB = $state['chd_db'];

		// start transaction
		$db = $this->getRepoMaster();
		$db->begin( __METHOD__ );

		try {
			$this->releaseClientLock( $wikiDB, $state['chd_lock'] );

			$state['chd_lock'] = null;
			$state['chd_seen'] = $seen;
			$state['chd_touched'] = wfTimestamp( TS_MW, time() );
			//XXX: use the DB's time to avoid clock skew?

			// insert state record with the new state.
			$db->update(
				$this->stateTable,
				$state,
				array( 'chd_site' => $state['chd_site'] ),
				__METHOD__
			);
		} catch ( \Exception $ex ) {
			$db->rollback( __METHOD__ );
			$this->releaseRepoMaster( $db );

			wfProfileOut( __METHOD__ );
			throw $ex;
		}

		$db->commit( __METHOD__ );
		$this->releaseRepoMaster( $db );

		$this->trace( "Released $wikiDB for site $siteID at $seen." );
		wfProfileOut( __METHOD__ );
	}

	/**
	 * Determines the name of the global lock that should be used to lock the given client.
	 *
	 * @param string $wikiDB: The logical database name of the wiki to lock
	 *
	 * @return string the lock name to use.
	 */
	protected function getClientLockName( $wikiDB ) {
		return "$wikiDB.WikiBase.dispatchChanges";
	}

	/**
	 * Tries to acquire a global lock on the given client wiki.
	 *
	 * The lock is acquired on the client wiki's master DB.
	 *
	 * @param string       $wikiDB The logical database name of the wiki for which to grab a lock.
	 * @param string|null  $lock  The name of the lock to acquire. If not given, getClientLockName()
	 *                     will be used to generate an appropriate name.
	 *
	 * @return String|bool The lock name if the lock was acquired, false otherwise.
	 */
	protected function getClientLock( $wikiDB, $lockName = null ) {
		wfProfileIn( __METHOD__ );

		$this->trace( "Trying to get client lock for $wikiDB" );

		if ( $lockName === null ) {
			$lockName = $this->getClientLockName( $wikiDB );
			$this->trace( "Lock name not defined for $wikiDB. Got lock name $lockName." );
		}

		$this->trace( "Trying to get client master for $wikiDB" );

		$db = $this->getClientMaster( $wikiDB );

		$ok = $db->lock( $lockName, __METHOD__ );
		$this->releaseClientMaster( $wikiDB, $db );

		$msg = $ok ? "Set lock for $wikiDB" : "Failed to set lock for $wikiDB";
		$this->trace( $msg );

		wfProfileOut( __METHOD__ );
		return $ok ? $lockName : false;
	}

	/**
	 * Releases the given global lock on the given client wiki.
	 *
	 * The lock is released on the client wiki's master DB.
	 *
	 * @param string  $wikiDB The logical database name of the wiki for which to release the lock.
	 * @param string  $lock  The name of the lock to release.
	 *
	 * @return bool whether the lock was released successfully.
	 */
	protected function releaseClientLock( $wikiDB, $lock ) {
		wfProfileIn( __METHOD__ );

		$db = $this->getClientMaster( $wikiDB );
		$ok = $db->unlock( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiDB, $db );

		wfProfileOut( __METHOD__ );
		return $ok;
	}

	/**
	 * Checks the given global lock on the given client wiki.
	 *
	 * The lock is checked on the client wiki's master DB.
	 *
	 * @param string  $wikiDB The logical database name of the wiki for which to check the lock.
	 * @param string  $lock  The name of the lock to check.
	 *
	 * @return bool true if the given lock is currently held by another process, false otherwise.
	 */
	protected function isClientLockUsed( $wikiDB, $lock ) {
		wfProfileIn( __METHOD__ );

		$db = $this->getClientMaster( $wikiDB );
		$free = $db->lockIsFree( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiDB, $db );

		wfProfileOut( __METHOD__ );
		return !$free;
	}

	/**
	 * Returns a batch of changes for the given client wiki, starting from the given position
	 * in the wb_changes table. The changes may be filtered to only include those changes that
	 * are relevant to the given client wiki. The number of changes returned by this method
	 * is limited by $this->batchSize. Changes are returned with IDs in ascending order.
	 *
	 * @note: due to programmatic filtering, this method may use multiple database queries to
	 * collect the changes for the next batch. The number of requests needed can be adjusted
	 * using $this->batchChunkFactor (via the 'dispatchBatchChunkFactor' setting).
	 *
	 * @param string $siteID:    The client wiki's global site identifier, as used by sitelinks.
	 * @param string $wikiDB:  The wiki for which to get the changes (used mainly for filtering).
	 * @param int $after:  The last change ID processed by a previous run. All changes returned
	 *                     will have an ID greater than $after.
	 *
	 * @return array ( $batch, $seen ), where $batch is a list of Change objects, and $seen
	 *         if the ID of the last change considered for the batch (even if that was filtered out),
	 *         for use as a continuation marker.
	 */
	public function getPendingChanges( $siteID, $wikiDB, $after ) {
		wfProfileIn( __METHOD__ );

		// Loop until we have a full batch of size $this->batchSize,
		// or there are no more changes to process.

		//NOTE: we could try to filter the changes directly in the DB, but
		//      that will no longer work once we have a client side usage tracking table
		//      for free-form use.

		$batch = array();
		$batchSize = 0;
		$chunkSize = $this->batchSize * $this->batchChunkFactor;

		// Track the change ID from which the next pass should start.
		// Note that this is non-trivial due to programmatic filtering.
		$lastIdSeen = $after;

		while ( $batchSize < $this->batchSize ) {
			// get a chunk of changes
			$chunk = $this->changesCache->loadChunk( $after+1, $chunkSize );

			if ( empty( $chunk ) ) {
				break; // no more changes
			}

			// start the next round here
			$last = end( $chunk );
			$after = $last->getId();
			reset( $chunk ); // don't leave the array pointer messy.

			// filter the changes in the chunk and add the result to the batch
			$remaining = $this->batchSize - $batchSize;
			list( $filtered, $lastIdSeen ) = $this->filterChanges( $siteID, $wikiDB, $chunk, $remaining );

			$batch = array_merge( $batch, $filtered );
			$batchSize = count( $batch );

			//XXX: We could try to adapt $chunkSize based on ratio of changes that get filtered out:
			//     $chunkSize = ( $this->batchSize - count( $batch ) ) * ( count_before / count_after );
		}

		wfProfileOut( __METHOD__ );

		$this->trace( "Got " . count( $batch ) . " pending changes. "
			. sprintf( "Cache hit rate is %2d%%", $this->changesCache->getHitRatio() * 100 ) );

		return array( $batch, $lastIdSeen );
	}

	/**
	 * Checks whether the given Change is somehow relevant to the given wiki site.
	 *
	 * In particular this check whether the Change modifies any sitelink that refers to the
	 * given wiki site.
	 *
	 * @note: this does not check whether the entity that was changes is or is not at all
	 *        connected with (resp. used on) the target wiki.
	 *
	 * @param Change $change the change to examine.
	 * @param string $siteID the site to consider.
	 *
	 * @return bool
	 */
	protected function isRelevantChange( Change $change, $siteID ) {
		wfProfileIn( __METHOD__ );

		if ( $change instanceof ItemChange && !$change->isEmpty() ) {
			$siteLinkDiff = $change->getSiteLinkDiff();

			if ( isset( $siteLinkDiff[ $siteID ] ) ) {
				return true;
			}
		}

		wfProfileOut( __METHOD__ );
		return false;
	}

	/**
	 * Filters a list of changes, removing changes not relevant to the given client wiki.
	 *
	 * Currently, we only keep ItemChanges for items that have a sitelink to the
	 * target client wiki.
	 *
	 * @param string   $siteID : The client wiki's global site identifier, as used by sitelinks.
	 * @param string   $wikiDB : The logical database name of the target wiki.
	 * @param Change[] $changes: The list of changes to filter.
	 * @param int      $limit:   The max number of changes to return
	 *
	 * @return array ( $batch, $seen ), where $batch is the filtered list of Change objects,
	 *         and $seen if the ID of the last change considered for the batch
	 *         (even if that was filtered out), for use as a continuation marker.
	 */
	protected function filterChanges( $siteID, $wikiDB, $changes, $limit ) {
		wfProfileIn( __METHOD__ );

		// collect all item IDs mentioned in the changes
		$itemSet = array();
		foreach ( $changes as $change ) {
			if ( $change instanceof ItemChange ) {
				$itemId = $change->getEntityId()->getNumericId();
				$itemSet[$itemId] = true;
			}
		}

		$this->trace( "Checking sitelinks to $siteID for " . count( $itemSet ) . " items." );

		// find all sitelinks from those items to $siteID
		// TODO: allow mock SiteLinkTable for testing!
		$table = new SiteLinkTable( 'wb_items_per_site', true, $this->repoDB );
		$links = $table->getLinks( array_keys( $itemSet ), array( $siteID ) );

		//XXX: Once we later have a client side usage tracking table, we need to also
		//     take that into account.

		// collect the item IDs present in these links
		$linkedItems = array();
		foreach ( $links as $link ) {
			list(,, $item ) = $link;
			$linkedItems[ $item ] = true;
		}

		$this->trace( "Retaining changes for " . count( $linkedItems ) . " relevant items." );

		// find all changes that relate to an item that has a sitelink to $siteID.
		$filteredChanges = array();
		$numberOfChangesFound = 0;
		$lastIdSeen = 0;
		foreach ( $changes as $change ) {
			$lastIdSeen = $change->getId();

			if ( $change instanceof ItemChange) {
				$itemId = $change->getEntityId()->getNumericId();

				// The change is relevant if it alters any sitelinks referring to $siteID,
				// or the item currently links to $siteID.
				if ( isset( $linkedItems[$itemId] )
					|| $this->isRelevantChange( $change, $siteID ) !== null ) {

					$filteredChanges[] = $change;
					$numberOfChangesFound++;
				}
			}

			if ( $numberOfChangesFound >= $limit ) {
				break;
			}
		}

		$this->trace( "Found " . count( $filteredChanges ) . " changes for items with relevant sitelinks." );

		wfProfileOut( __METHOD__ );
		return array( $filteredChanges, $lastIdSeen );
	}

	/**
	 * Notifies the client wiki of the given changes.
	 *
	 * This is done by posting a ChangeNotificationJob to the target wiki's job queue.
	 * The WikibaseClient extension must be active on the client wiki.
	 *
	 * @param string   $siteID:  The client wiki's global site identifier, as used by sitelinks.
	 * @param string   $wikiDB:  The logical db name of the wiki to post to
	 * @param Change[] $changes: The list of changes to post to the wiki.
	 *
	 * @throws \MWException if the notification job could to be posted to the target wiki
	 */
	public function postChangeJobs( $siteID, $wikiDB, array $changes ) {
		if ( empty( $changes ) ) {
			return; // nothing to do
		}

		wfProfileIn( __METHOD__ );

		//TODO: allow a mock JQG for testing
		wfProfileIn( __METHOD__ . '#queue' );
		$qgroup = \JobQueueGroup::singleton( $wikiDB );
		wfProfileOut( __METHOD__ . '#queue' );

		if ( !$qgroup ) {
			throw new \MWException( "Failed to acquire a JobQueueGroup for $wikiDB" );
		}

		wfProfileIn( __METHOD__ . '#job' );
		$job = ChangeNotificationJob::newFromChanges( $changes, $this->repoDB );
		wfProfileOut( __METHOD__ . '#job' );

		wfProfileIn( __METHOD__ . '#push' );
		$ok = $qgroup->push( $job );
		wfProfileOut( __METHOD__ . '#push' );

		if ( !$ok ) {
			wfProfileOut( __METHOD__ );
			throw new \MWException( "Failed to push to job queue for $wikiDB" );
		}

		$this->trace( "Posted notification job for site $siteID with "
			. count( $changes ) . " changes to $wikiDB." );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Log a message if verbose mode is enabled
	 *
	 * @param string $message
	 */
	public function trace( $message ) {
		if ( $this->verbose ) {
			$this->log( "    " . $message );
		}
	}

	/**
	 * Log a message unless we are quiet.
	 *
	 * @param string $message
	 */
	public function log( $message ) {
		$this->output( date( 'H:i:s' ) . ' ' . $message . "\n", 'dispatchChanges::log' );
		$this->cleanupChanneled();
	}

}

$maintClass = 'Wikibase\DispatchChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );
