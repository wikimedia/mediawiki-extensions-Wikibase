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

		$wikiDB = $wikiState['chd_db'];
		$siteID = $wikiState['chd_site'];
		$after = intval( $wikiState['chd_seen'] );

		$this->log( "Processing changes for $wikiDB" );

		// get relevant changes
		list( $changes, $continueAfter ) = $this->getPendingChanges( $siteID, $wikiDB, $after );

		// notify the client wiki about the changes
		$this->postChangeJobs( $siteID, $wikiDB, $changes ); // does nothing if $changes is empty

		$this->releaseClient( $continueAfter, $wikiState );

		$n = count( $changes );

		$this->log( "Posted $n changes to $wikiDB" );
		return $n;
	}

	/**
	 * Selects a client wiki at random and locks it. If no unlocked client wiki can be found after
	 * $this->maxSelectTries, this method throws a MWException.
	 *
	 * @return array An associative array containing the state of the selected client wiki.
	 *               Fields are:
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
		for ( $i=0; $i < $this->maxSelectTries; $i++ ) {
			try {
				$state = $this->trySelectClient();

				if ( $state ) {
					// got one
					return $state;
				}
			} catch ( \DBError $ex ) {
				$this->log( "ERROR: $ex" );
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
		// XXX: use weights?!
		$siteID = array_rand( $this->clientWikis );
		$wikiDB = $this->clientWikis[ $siteID ];
		$isNew = false;

		$this->trace( "Trying $siteID" );

		// start transaction
		$db = $this->getRepoMaster();
		$db->begin( __METHOD__ );

		try {

			// get client state
			$state = $db->selectRow(
				$this->stateTable,
				array( 'chd_site', 'chd_db', 'chd_seen', 'chd_touched', 'chd_lock', 'chd_disabled' ),
				array( 'chd_site' => $siteID ),
				__METHOD__,
				array( 'FOR UPDATE' )
			);

			if ( !$state ) {
				// if that client wiki isn't in the DB yet, pretend and using defaults.
				$state = array(
					'chd_site' => $siteID,
					'chd_db' => $wikiDB,
					'chd_seen' => 0,
					'chd_touched' => '00000000000000',
					'chd_lock' => null,
					'chd_disabled' => 0,
				);

				$this->trace( "$siteID is not in the dispatch table yet." );
				$isNew = true;
			} else {
				// turn the row object into an array
				$state = get_object_vars( $state );
			}

			if ( $state['chd_disabled'] ) {
				// this wiki is disabled
				$this->trace( "Updates to $siteID are disabled." );

				$db->rollback( __METHOD__ );
				$this->releaseRepoMaster( $db );

				return false;
			}

			if ( $state['chd_lock'] !== null ) {

				// bail out if another dispatcher instance is holding a lock for that wiki
				if ( $this->isClientLockUsed( $wikiDB, $state['chd_lock'] ) ) {
					$this->trace( "$siteID is already being handled by another process." );

					$db->rollback( __METHOD__ );
					$this->releaseRepoMaster( $db );

					return false;
				}
			}

			$lock = $this->getClientLock( $wikiDB );

			if ( $lock === false ) {
				// This really shouldn't happen, since we already checked if another process has a lock.
				// The write lock we are holding on the wb_changes_dispatch table should be preventing
				// any race conditions.
				// However, another process may still hold the lock if it grabed it without locking
				// wb_changes_dispatch, or if it didn't record the lock in wb_changes_dispatch.

				$this->log( "Failed to acquire lock on $wikiDB for site $siteID!" );

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
					array( 'chd_site' => $state['chd_site'] ),
					__METHOD__
				);
			}
		} catch ( \Exception $ex ) {
			$db->rollback( __METHOD__ );
			$this->releaseRepoMaster( $db );
			throw $ex;
		}

		$db->commit( __METHOD__ );
		$this->releaseRepoMaster( $db );

		$this->trace( "Locked $wikiDB for site $siteID at {$state['chd_seen']}." );

		unset( $state['chd_disabled'] ); // don't mess with this.
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
			//XXX: use the DB's time to avoid clock skew? But that timestamp is just informative anyway.

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
			throw $ex;
		}

		$db->commit( __METHOD__ );
		$this->releaseRepoMaster( $db );

		$this->trace( "Released $wikiDB for site $siteID at $seen." );
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
	protected function getClientLock( $wikiDB, $lock = null ) {
		if ( $lock === null ) {
			$lock = $this->getClientLockName( $wikiDB );
		}

		$db = $this->getClientMaster( $wikiDB );
		$ok = $db->lock( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiDB, $db );

		return $ok ? $lock : false;
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
		$db = $this->getClientMaster( $wikiDB );
		$ok = $db->unlock( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiDB, $db );

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
		$db = $this->getClientMaster( $wikiDB );
		$free = $db->lockIsFree( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiDB, $db );

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
			$chunk = $this->filterChanges( $siteID, $wikiDB, $chunk );
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
			$this->trace( "Found no new changes for $siteID, at $seen." );
		} else {
			$this->trace( "Loaded a batch of " . count( $batch ) . " changes for $siteID, up to $seen." );
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
		if ( $change instanceof ItemChange ) {
			$siteLinkDiff = $change->getSiteLinkDiff();

			if ( isset( $siteLinkDiff[ $siteID ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filters a list of changes, removing changes not relevant to the given client wiki.
	 *
	 * Currently, we only keep ItemChanges for items that have a sitelink to the
	 * target client wiki.
	 *
	 * @param string $siteID:    The client wiki's global site identifier, as used by sitelinks.
	 * @param string $wikiDB:    The logical database name of the target wiki.
	 * @param Change[] $changes: The list of changes to filter.
	 *
	 * @return Change[] list of Change object from $changes that are relevant to $siteID.
	 */
	protected function filterChanges( $siteID, $wikiDB, $changes ) {
		// collect all item IDs mentioned in the changes
		$itemSet = array();
		foreach ( $changes as $change ) {
			if ( $change instanceof ItemChange ) {
				$itemId = $change->getEntityId()->getNumericId();
				$itemSet[$itemId] = true;
			}
		}

		$this->trace( "checking sitelinks to $siteID for " . count( $itemSet ) . " items." );

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

		$this->trace( "retaining changes for  " . count( $linkedItems ) . " relevant items." );

		// find all changes that relate to an item that has a sitelink to $siteID.
		$keep = array();
		foreach ( $changes as $change ) {
			if ( $change instanceof ItemChange) {
				$itemId = $change->getEntityId()->getNumericId();

				// The change is relevant if it alters any sitelinks refering to $siteID,
				// of the item is currently links to $siteID.
				if ( isset( $linkedItems[$itemId] )
					|| $this->isRelevantChange( $change, $siteID ) !== null ) {
					$keep[] = $change;
				}
			}
		}

		$changes = $keep;

		$this->trace( "found " . count( $changes ) . " changes for items with relevant sitelinks." );

		return $changes;
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

		//TODO: allow a mock JQG for testing
		$qgroup = \JobQueueGroup::singleton( $wikiDB );

		if ( !$qgroup ) {
			throw new \MWException( "Failed to acquire a JobQueueGroup for $wikiDB" );
		}

		$job = ChangeNotificationJob::newFromChanges( $changes, $this->repoDB );
		$ok = $qgroup->push( $job );

		if ( !$ok ) {
			throw new \MWException( "Failed to push to job queue for $wikiDB" );
		}

		$this->trace( "Posted notification job for " . count( $changes )
			. " changes to $wikiDB for site $siteID." );
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
