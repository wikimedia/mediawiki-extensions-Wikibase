<?php

namespace Wikibase;

use ChangeRequeuer;
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

		$requeuer = new ChangeRequeuer( wfGetLB() );

		$this->output( "Getting change rows in range.\n" );
		$changeIds = $requeuer->getChangeRowIdsInRange( $cutoffFrom, $cutoffTo );
		$this->output( count( $changeIds ) . " rows got.\n" );

		$this->output( "Running batch inserts.\n" );
		$requeuer->batchRequeueRows( $changeIds, $this->mBatchSize );
		$this->output( "Done.\n" );
	}

}

$maintClass = RequeueChanges::class;
require_once RUN_MAINTENANCE_IF_MAIN;
