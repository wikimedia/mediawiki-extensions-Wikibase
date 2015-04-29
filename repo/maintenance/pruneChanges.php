<?php

namespace Wikibase;

use Maintenance;
use Wikibase\Lib\PidLock;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\ChangePruner;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Prune the Wikibase changes table to a maximum number of entries.
 */
class PruneChanges extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Prune the Wikibase changes table to a maximum number of entries";

		$this->addOption( 'number-of-days', 'Keep changes at least N days (deprecated).', false, true, 'n' );
		$this->addOption( 'keep-days',  'Keep changes at least N days.', false, true, 'd' );
		$this->addOption( 'keep-hours', 'Keep changes at least N hours.', false, true, 'h' );
		$this->addOption( 'keep-minutes', 'Keep changes at least N minutes.', false, true, 'm' );
		$this->addOption( 'grace-minutes', 'Keep changes at least N more minutes after they have been dispatched.', false, true, 'g' );

		$this->addOption( 'force', 'Run regardless of whether the PID file says it is running already.',
						 false, false, 'f' );

		$this->addOption( 'ignore-dispatch', 'Ignore whether changes have been dispatched or not.',
						false, false, 'D' );

		$this->setBatchSize( 500 );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$force = $this->getOption( 'force', false );
		$pidLock = new PidLock( 'WBpruneChanges', wfWikiID() );

		if ( !$pidLock->getLock( $force ) ) {
			$this->output( date( 'H:i:s' ) . " already running, exiting\n" );
			exit( 5 );
		}

		$changePruner = new ChangePruner(
			$this->mBatchSize,
			$this->getKeepSeconds(),
			$this->getGraceSeconds(),
			$this->getOption( 'ignore-dispatch', false )
		);

		$changePruner->setMessageReporter( $this->newMessageReporter() );
		$changePruner->prune();

		$pidLock->removeLock(); // delete lockfile on normal exit
	}

	private function getKeepSeconds() {
		$keepSeconds = 0;
		$keepSeconds += (int)$this->getOption( 'number-of-days', 0 ) * 24 * 60 * 60;
		$keepSeconds += (int)$this->getOption( 'keep-days', 0 ) * 24 * 60 * 60;
		$keepSeconds += (int)$this->getOption( 'keep-hours', 0 ) * 60 * 60;
		$keepSeconds += (int)$this->getOption( 'keep-minutes', 0 ) * 60;

		if ( $keepSeconds === 0 ) {
			// one day
			$keepSeconds = 1 * 24 * 60 * 60;
		}

		return $keepSeconds;
	}

	private function getGraceSeconds() {
		$graceSeconds = 0;
		$graceSeconds += (int)$this->getOption( 'grace-minutes', 0 ) * 60;

		if ( $graceSeconds === 0 ) {
			// one hour
			$graceSeconds = 1 * 60 * 60;
		}

		return $graceSeconds;
	}

	/**
	 * @return MessageReporter
	 */
	private function newMessageReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'log' ) );

		return $reporter;
	}

	/**
	 * Log a message unless we are quiet.
	 *
	 * @param string $message
	 */
	public function log( $message ) {
		$this->output( date( 'H:i:s' ) . ' ' . $message . "\n", 'pruneChanges::log' );
		$this->cleanupChanneled();
	}

}

$maintClass = 'Wikibase\PruneChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );
