<?php

namespace Wikibase;

use Maintenance;
use MWException;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that will re-queue changes between two given timestamps.
 *
 * This makes the assumption that clients just purge a whole page no matter which bit of
 * data has been changed.
 * If this is no longer the case all changes from an entity since the first to be re-queued
 * should also be re-queued
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class RequeueChanges extends Maintenance {

	/**
	 * @var bool
	 */
	private $verbose;

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			'Maintenance script that re-queues changes between the two given timestamps.'
		);

		$this->addOption(
			'from',
			"Only rebuild rows in requested time range (in YYYYMMDDHHMMSS format)",
			true,
			true
		);
		$this->addOption(
			'to',
			"Only rebuild rows in requested time range (in YYYYMMDDHHMMSS format)",
			true,
			true
		);
		$this->setBatchSize( 200 );
	}

	public function execute() {
		$cutoffFrom = wfTimestamp( TS_MW, $this->getOption( 'from' ) );
		$cutoffTo = wfTimestamp( TS_MW, $this->getOption( 'to' ) );
		$this->output( "Running from $cutoffFrom to $cutoffTo.\n" );

		$this->output( "Getting change rows in range.\n" );
		$changeIds = $this->getChangeRowIdsInRange( $cutoffFrom, $cutoffTo );
		$this->output( count( $changeIds ) . " rows got.\n" );

		$this->batchRequeueRows( $changeIds );
		$this->output( "Done.\n" );
	}

	/**
	 * @param string $from timestamp accepted by wfTimestamp
	 * @param string $to timestamp accepted by wfTimestamp
	 *
	 * @return array
	 * @throws MWException
	 */
	private function getChangeRowIdsInRange( $from, $to ) {
		$dbr = $this->getDB( DB_SLAVE );

		$ids = $dbr->selectFieldValues(
			'wb_changes',
			'change_id',
			array(
				'change_time >= ' . $dbr->addQuotes( $dbr->timestamp( $from ) ),
				'change_time <= ' . $dbr->addQuotes( $dbr->timestamp( $to ) ),
			),
			__METHOD__
		);

		if ( !$ids ) {
			throw new MWException( 'Failed to get change row ids.' );
		}

		return $ids;
	}

	/**
	 * @param array $changeIds
	 *
	 * @throws \DBReplicationWaitError
	 */
	private function batchRequeueRows( array $changeIds ) {
		$dbw = $this->getDB( DB_MASTER );
		foreach ( array_chunk( $changeIds, $this->mBatchSize ) as $changeIdBatch ) {
			$dbw->insertSelect(
				'wb_changes',
				'wb_changes',
				array(
					'change_type' => 'change_type',
					'change_time' => 'change_time',
					'change_object_id' => 'change_object_id',
					'change_revision_id' => 'change_revision_id',
					'change_user_id' => 'change_user_id',
					'change_info' => 'change_info',
				),
				array(
					'change_id' => $changeIdBatch
				)
			);
			$this->output( "Batch complete.\n" );
			wfGetLBFactory()->waitForReplication();
		}
	}

}

$maintClass = RequeueChanges::class;
require_once RUN_MAINTENANCE_IF_MAIN;
