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
	 * @var string: the logical name of the repository's database
	 */
	protected $repoDB;

	/**
	 * @var string[]: list of logical names of local client wiki databases
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
	 * @var int: the number of client update passes to perform before exiting.
	 */
	protected $passes;

	/**
	 * @var int: the number of seconds to wait before executing the next pass.
	 */
	protected $delay;

	/**
	 * @var int: the number of times to try to lock a client wiki before giving up.
	 */
	protected $maxSelectTries;

	/**
	 * @var bool: whether output should be version.
	 */
	protected $verbose;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->mDescription =
			'Maintenance script that polls for Wikibase changes in the shared wb_changes table
			and dispatches them to any client wikis using their job queue.';

		$this->addOption( 'verbose', "Report activity." );
		$this->addOption( 'max-select-tries', "How often to try to find an idle client wiki before giving up. Default: 10", false, true );
		$this->addOption( 'pass-delay', "Seconds to sleep between passes. Default: 1", false, true );
		$this->addOption( 'passes', "The number of passes to do before exiting. Default: the number of client wikis.", false, true );
		$this->addOption( 'batch-size', "How many changes to pass to a client at a time. Default: 100", false, true );

		parent::__construct();
	}

	/**
	 * Initializes members from command line options and configuration settings.
	 */
	protected function handleOptions() {
		$this->stateTable = 'wb_changes_dispatch';

		$this->repoDB = Settings::get( 'changesDatabase' );
		$this->clientWikis = Settings::get( 'localClientDatabases' );
		$this->batchChunkFactor = Settings::get( 'dispatchBatchChunkFactor' );

		$this->batchSize = intval( $this->getOption( 'batch-size', 100 ) );
		$this->passes = intval( $this->getOption( 'passes', count( $this->clientWikis ) ) );
		$this->delay = intval( $this->getOption( 'pass-delay', 1 ) );

		$this->maxSelectTries = intval( $this->getOption( 'max-select-tries', 10 ) );
		$this->verbose = $this->getOption( 'verbose', false );
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
	 * This will run $this->runPass() in a loop, the number of times specified by $this->passes,
	 * sleeping $this->delay seconds between passes.
	 */
	public function execute() {
		if ( !defined( 'WBL_VERSION' ) ) {
			// Since people might waste time debugging odd errors when they forget to enable the extension. BTDT.
			die( 'WikibaseLib has not been loaded.' );
		}

		$this->handleOptions();

		$this->log( "Starting loop for {$this->passes} passes" );

		//run passes in a loop, and sleep between passes.
		for ( $i = 0; $i < $this->passes; $i++ ) {
			if ( $i && $this->delay > 0 ) {
				// sleep before all but the first pass
				sleep( $this->delay );
			}

			try {
				$this->runPass();
			} catch ( \Exception $ex ) {
				$this->log( "ERROR: $ex" );
			}
		}

		$this->log( "Done, exiting." );
	}

	/**
	 * Performs one update pass. This involves the following steps:
	 *
	 * 1) Pick a client wiki at random and lock it against other dispatch processes.
	 * 2) Get a batch of changes for the client wiki.
	 * 3) Post a notification job to the client wiki's job queue.
	 * 4) Update the dispatch log for the client wiki, and release it.
	 */
	public function runPass() {
		$this->trace( "Picking a client wiki..." );
		$wikiState = $this->selectClient();

		$wikiId = $wikiState['chd_wiki'];
		$after = intval( $wikiState['chd_seen'] );

		$this->log( "Processing changes for $wikiId" );

		// get relevant changes
		list( $changes, $continueAfter ) = $this->getPendingChanges( $wikiId, $after );

		// notify the client wiki about the changes
		$this->postChangeJobs( $wikiId, $changes ); // does nothing if $changes is empty

		$this->releaseClient( $continueAfter, $wikiState );

		$n = count( $changes );

		$this->trace( "Posted $n changes to $wikiId" );
		return $n;
	}

	/**
	 * Selects a client wiki at random and locks it. If no unlocked client wiki can be found after
	 * $this->maxSelectTries, this method throws a MWException.
	 *
	 * @return array An associative array containing the state of the selected client wiki.
	 *               Fields are:
	 * * chd_wiki:     the client wiki's logical database name
	 * * chd_seen:     the last change ID processed for that client wiki
	 * * chd_touched:  timestamp giving the last time that client wiki was updated
	 * * chd_lock:     the name of a global lock currently active for that client wiki
	 *
	 * @throws \MWException if no available client wiki could be found.
	 *
	 * @see releaseWiki()
	 */
	protected function selectClient() {
		for ( $i=0; $i < $this->maxSelectTries; $i++ ) {
			$state = $this->trySelectClient();

			if ( $state ) {
				// got one
				return $state;
			}
		}

		throw new \MWException( "Could not lock a client wiki after " . $this->maxSelectTries . " tries!" );
	}

	/**
	 * Selects a client wiki at random and tries to lock it. If it can't be locked because
	 * another dispatch process is working on it, this method returns false.
	 *
	 * @return array An associative array containing the state of the selected client wiki
	 *               (see selectClient()) or false of the client wiki is not available.
	 *
	 * @see selectClient()
	 * @throws \MWException if there are no client wikis to chose from.
	 */
	protected function trySelectClient() {
		if ( empty( $this->clientWikis ) ) {
			throw new \MWException( "no client wikis configured!" );
		}

		// pick a client at random
		//TODO: use weights
		$wikiId = $this->clientWikis[ array_rand( $this->clientWikis ) ];
		$isNew = false;

		$this->trace( "Trying $wikiId" );

		// start transaction
		$db = $this->getRepoMaster();
		$db->begin( __METHOD__ );

		// get client state
		$state = $db->selectRow(
			$this->stateTable,
			array( 'chd_wiki', 'chd_seen', 'chd_touched', 'chd_lock' ),
			array( 'chd_wiki' => $wikiId, 'chd_disabled' => 0 ),
			__METHOD__,
			array( 'FOR UPDATE' )
		);

		if ( !$state ) {
			// if that client wiki isn't in the DB yet, pretend and using defaults.
			$state = array(
				'chd_wiki' => $wikiId,
				'chd_seen' => 0,
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
			);

			$this->trace( "$wikiId is not in the dispatch table yet." );
			$isNew = true;
		} else {
			// turn the row object into an array
			$state = get_object_vars( $state );
		}

		if ( $state['chd_lock'] !== null ) {

			// bail out if another dispatcher instance is holding a lock for that wiki
			if ( $this->isClientLockUsed( $wikiId, $state['chd_lock'] ) ) {
				$this->trace( "$wikiId is already being handled by another process." );

				$db->rollback( __METHOD__ );
				$this->releaseRepoMaster( $db );

				return false;
			}
		}

		$lock = $this->getClientLock( $wikiId );

		if ( $lock === false ) {
			// This really shouldn't happen, since we already checked if another process has a lock.
			// The write lock we are holding on the wb_changes_dispatch table should be preventing
			// any race conditions.
			// However, another process may still hold the lock if it grabed it without locking
			// wb_changes_dispatch, or if it didn't record the lock in wb_changes_dispatch.

			$this->log( "Failed to acquire lock on $wikiId!" );

			$db->rollback( __METHOD__ );
			$this->releaseRepoMaster( $db );

			return false;
		}

		$state['chd_lock'] = $lock;

		if ( $isNew ) {
			// insert state record for previously unknown client wiki
			$db->insert(
				$this->stateTable,
				$state,
				__METHOD__
			);
		} else {
			// update state record for already known client wiki
			$db->update(
				$this->stateTable,
				$state,
				array( 'chd_wiki' => $state['chd_wiki'] ),
				__METHOD__
			);
		}

		$db->commit( __METHOD__ );
		$this->releaseRepoMaster( $db );

		$this->trace( "Locked $wikiId at {$state['chd_seen']}." );
		return $state;
	}

	/**
	 * Updates the given client wiki's entry in the dispatch table and
	 * releases the global lock on that wiki.
	 *
	 * @param int   $seen:  the ID of the last change processed in the pass.
	 * @param array $state: associative array representing the client wiki's state before the
	 *                      update pass, as returned by selectWiki().
	 *
	 * @see selectWiki()
	 */
	protected function releaseClient( $seen, array $state ) {
		$wikiId = $state['chd_wiki'];

		// start transaction
		$db = $this->getRepoMaster();
		$db->begin( __METHOD__ );

		$this->releaseClientLock( $wikiId, $state['chd_lock'] );

		$state['chd_lock'] = null;
		$state['chd_seen'] = $seen;
		$state['chd_touched'] = wfTimestamp( TS_MW, time() );
		//XXX: use the DB's time to avoid clock skew? But that timestamp is just informative anyway.

		// insert state record with the new state.
		$db->update(
			$this->stateTable,
			$state,
			array( 'chd_wiki' => $state['chd_wiki'] ),
			__METHOD__
		);

		$db->commit( __METHOD__ );
		$this->releaseRepoMaster( $db );

		$this->trace( "Released $wikiId at $seen." );
	}

	/**
	 * Determines the name of the global lock that should be used to lock the given client.
	 *
	 * @param string $wikiId: The logical database name of the wiki to lock
	 *
	 * @return string the lock name to use.
	 */
	protected function getClientLockName( $wikiId ) {
		return "$wikiId.WikiBase.dispatchChanges";
	}

	/**
	 * Tries to acquire a global lock on the given client wiki.
	 *
	 * The lock is acquired on the client wiki's master DB.
	 *
	 * @param string       $wikiId The logical database name of the wiki for which to grab a lock.
	 * @param string|null  $lock  The name of the lock to acquire. If not given, getClientLockName()
	 *                     will be used to generate an appropriate name.
	 *
	 * @return String|bool The lock name if the lock was acquired, false otherwise.
	 */
	protected function getClientLock( $wikiId, $lock = null ) {
		if ( $lock === null ) {
			$lock = $this->getClientLockName( $wikiId );
		}

		$db = $this->getClientMaster( $wikiId );
		$ok = $db->lock( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiId, $db );

		return $ok ? $lock : false;
	}

	/**
	 * Releases the given global lock on the given client wiki.
	 *
	 * The lock is released on the client wiki's master DB.
	 *
	 * @param string  $wikiId The logical database name of the wiki for which to release the lock.
	 * @param string  $lock  The name of the lock to release.
	 *
	 * @return bool whether the lock was released successfully.
	 */
	protected function releaseClientLock( $wikiId, $lock ) {
		$db = $this->getClientMaster( $wikiId );
		$ok = $db->unlock( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiId, $db );

		return $ok;
	}

	/**
	 * Checks the given global lock on the given client wiki.
	 *
	 * The lock is checked on the client wiki's master DB.
	 *
	 * @param string  $wikiId The logical database name of the wiki for which to check the lock.
	 * @param string  $lock  The name of the lock to check.
	 *
	 * @return bool true if the given lock is currently held by another process, false otherwise.
	 */
	protected function isClientLockUsed( $wikiId, $lock ) {
		$db = $this->getClientMaster( $wikiId );
		$free = $db->lockIsFree( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiId, $db );

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
	 * @param string $wikiId:  The wiki for which to get the changes (used mainly for filtering).
	 * @param int $after:  The last change ID processed by a previous run. All changes returned
	 *                     will have an ID greater than $after.
	 *
	 * @return array ( $batch, $seen ), where $batch is a list of Change objects, and $seen
	 *         if the ID of the last change considered for the batch (even if that was filtered out),
	 *         for use as a continuation marker.
	 */
	public function getPendingChanges( $wikiId, $after ) {
		//TODO: allow a mock ChangesTable for testing
		$table = new ChangesTable( $this->repoDB );

		// Loop until we have a full batch of size $this->batchSize,
		// or there are no more changes to process.

		//NOTE: we could try to filter the changes directly in the DB, but
		//      that will no longer work once we have a client side usage tracking table
		//      for free-form use.

		$batch = array();
		$chunkSize = $this->batchSize * $this->batchChunkFactor;

		// Track the change ID from which the next pass should start.
		// Note that this is non-trivial due to programmatic filtering.
		$seen = $after;

		while ( count( $batch ) < $this->batchSize ) {
			// get a chunk of changes
			$chunk = $this->selectChanges( $table, $after, $chunkSize );

			if ( empty( $chunk ) ) {
				break; // no more changes
			}

			// start the next round here
			$last = end( $chunk );
			$after = $last->getId();
			reset( $chunk ); // don't leave the array pointer messy.

			// filter the changes in the chunk and add the result to the batch
			$chunk = $this->filterChanges( $wikiId, $chunk );
			$batch = array_merge( $batch, $chunk );

			// truncate the batch if needed.
			if ( count( $batch ) > $this->batchSize ) {
				// We need to find and remember the first change that gets cur off,
				// so we can continue from that change on the next pass.

				/* @var Change $anchor */
				list( $anchor ) = array_slice( $batch, $this->batchSize, 1 );
				$seen = $anchor->getId() -1;

				$batch = array_slice( $batch, 0, $this->batchSize );
				break;
			} else {
				$seen = $last->getId();
			}

			//XXX: We could try to adapt $chunkSize based on ratio of changes that get filtered out:
			//     $chunkSize = ( $this->batchSize - count( $batch ) ) * ( count_before / count_after );
		}

		if ( $batch === array() ) {
			$this->trace( "Found no new changes for $wikiId, at $seen." );
		} else {
			$this->trace( "Loaded a batch of " . count( $batch ) . " changes for $wikiId, up to $seen." );
		}

		return array( $batch, $seen );
	}

	/**
	 * Returns a list of Change objects loaded from $table.
	 * The list will have at most $limit entries, all IDs will be greater than $after,
	 * and it will be sorted with IDs in ascending order.
	 *
	 * @param ChangesTable $table The ChangesTable to query.
	 * @param int $after: The change ID from which to start
	 * @param int $limit: The maximum number of changes to return
	 *
	 * @return Change[] any changes matching the above criteria.
	 */
	public function selectChanges( ChangesTable $table, $after, $limit ) {
		$changes = $table->selectObjects(
			null,
			array(
				'id > ' . intval( $after )
			),
			array(
				'LIMIT' => $limit,
				'ORDER BY ' . $table->getPrefixedField( 'id' ) . ' ASC'
			),
			__METHOD__
		);

		return $changes;
	}

	/**
	 * Filters a list of changes, removing changes not relevant to the given client wiki.
	 *
	 * @param string $wikiId:    the logical database name of the target wiki.
	 * @param Change[] $changes: the list of changes to filter
	 *
	 * @return Change[] list of Change object from $changes that are relevant to $wikiId.
	 */
	protected function filterChanges( $wikiId, $changes ) {
		//TODO: Filter the changes using knowledge about how pages on the client wiki are
		//     associated with entities on the repository.
		//
		//     I.e. as long as Items are only used on the client page that is linked from that Item,
		//     we can filter them based on the repo's items_per_site table. If we later have a
		//     client side usage tracking table, we need to also take that into account.
		//
		//     For now though, we leave all that to the client, which means a lot of useless
		//     jobs for lots of small wikis.

		return $changes;
	}

	/**
	 * Notifies the client wiki of the given changes.
	 *
	 * This is done by posting a ChangeNotificationJob to the target wiki's job queue.
	 * The WikibaseClient extension must be active on the client wiki.
	 *
	 * @param string   $wikiId The logical db name of the wiki to post to
	 * @param Change[] $changes The list of changes to post to the wiki.
	 *
	 * @throws \MWException if the notification job could to be posted to the target wiki
	 */
	public function postChangeJobs( $wikiId, array $changes ) {
		if ( empty( $changes ) ) {
			return; // nothing to do
		}

		$job = new ChangeNotificationJob( $changes, $this->repoDB );

		//TODO: allow a mock JQG for testing
		$qgroup = \JobQueueGroup::singleton( $wikiId );

		if ( !$qgroup ) {
			throw new \MWException( "Failed to acquire a JobQueueGroup for $wikiId" );
		}

		$ok = $qgroup->push( $job );

		if ( !$ok ) {
			throw new \MWException( "Failed to push to job queue for $wikiId" );
		}

		$this->trace( "Posted notification job to $wikiId." );
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
