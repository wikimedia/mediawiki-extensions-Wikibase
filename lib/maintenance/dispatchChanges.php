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

	protected $stateTable = 'wb_changes_dispatch';

	protected $repoDB = false;

	protected $clientWikis = array();

	protected $batchSize = 100;

	protected $batchChunkFactor = 3; // a wild guess.

	protected $passes = 10;

	protected $delay = 1;

	protected $maxSelectTries = 10;

	protected $verbose = false;

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

	protected function handleOptions() {
		$this->repoDB = Settings::get( 'changesDatabase' );
		$this->clientWikis = Settings::get( 'localClientDatabases' );
		$this->batchChunkFactor = Settings::get( 'dispatchBatchChunkFactor' );

		$this->batchSize = intval( $this->getOption( 'batch-size', 100 ) );
		$this->passes = intval( $this->getOption( 'passes', count( $this->clientWikis ) ) );
		$this->delay = intval( $this->getOption( 'pass-delay', 1 ) );

		$this->maxSelectTries = intval( $this->getOption( 'max-select-tries', 10 ) );
		$this->verbose = $this->getOption( 'verbose', false );
	}

	protected function getRepoLB() {
		return wfGetLB( $this->repoDB );
	}

	protected function getClientLB( $wiki ) {
		return wfGetLB( $wiki );
	}

	protected function getRepoMaster() {
		return $this->getRepoLB()->getConnection( DB_MASTER, array(), $this->repoDB );
	}

	protected function getClientMaster( $wiki ) {
		return $this->getClientLB( $wiki )->getConnection( DB_MASTER, array(), $wiki );
	}

	protected function releaseRepoMaster( \DatabaseBase $db ) {
		$this->getRepoLB()->reuseConnection( $db );
	}

	protected function releaseClientMaster( $wiki, \DatabaseBase $db  ) {
		$this->getClientLB( $wiki )->reuseConnection( $db );
	}

	/**
	 * Maintenance script entry point.
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
	 * @return array|bool|object
	 * @throws \MWException
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
	 * @return array|bool|object
	 * @throws \MWException
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

		$state['chd_lock'] = $this->getClientLock( $wikiId );

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

	protected function getClientLockName( $wikiId ) {
		return "$wikiId.WikiBase.dispatchChanges";
	}

	protected function getClientLock( $wikiId, $lock = null ) {
		if ( $lock === null ) {
			$lock = $this->getClientLockName( $wikiId );
		}

		$db = $this->getClientMaster( $wikiId );
		$ok = $db->lock( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiId, $db );

		return $ok ? $lock : false;
	}

	protected function releaseClientLock( $wikiId, $lock ) {
		$db = $this->getClientMaster( $wikiId );
		$ok = $db->unlock( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiId, $db );

		return $ok;
	}

	protected function isClientLockUsed( $wikiId, $lock ) {
		$db = $this->getClientMaster( $wikiId );
		$free = $db->lockIsFree( $lock, __METHOD__ );
		$this->releaseClientMaster( $wikiId, $db );

		return !$free;
	}


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
	 * Handle a message (ie display and logging)
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
