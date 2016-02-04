<?php

namespace Wikibase;

use Exception;
use Maintenance;
use MWException;
use RequestContext;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Lib\Store\ChangeLookup;
use Wikibase\Repo\ChangeDispatcher;
use Wikibase\Repo\Notifications\JobQueueChangeNotificationSender;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\Sql\SqlChangeDispatchCoordinator;
use Wikibase\Store\Sql\SqlSubscriptionLookup;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that polls for Wikibase changes in the shared wb_changes table
 * and dispatches the relevant changes to any client wikis' job queues.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DispatchChanges extends Maintenance {

	/**
	 * @var bool
	 */
	private $verbose;

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			'Maintenance script that polls for Wikibase changes in the shared wb_changes table
			and dispatches them to any client wikis using their job queue.' );

		$this->addOption( 'verbose', "Report activity." );
		$this->addOption( 'idle-delay', "Seconds to sleep when idle. Default: 10", false, true );
		$this->addOption( 'dispatch-interval', "How often to dispatch to each target wiki. "
					. "Default: every 60 seconds", false, true );
		$this->addOption( 'lock-grace-interval', "Seconds after which to probe for orphaned locks. "
					. "Default: 60", false, true );
		$this->addOption( 'randomness', "Number of least current target wikis to pick from at random. "
					. "Default: 10.", false, true );
		$this->addOption( 'max-passes', "The number of passes to perform. "
					. "Default: 1 if --max-time is not set, infinite if it is.", false, true );
		$this->addOption( 'max-time', "The number of seconds to run before exiting, "
					. "if --max-passes is not reached. Default: infinite.", false, true );
		$this->addOption( 'max-chunks', 'Maximum number of chunks or passes per wiki when '
			. 'selecting pending changes. Default: 15', false, true );
		$this->addOption( 'batch-size', 'Maximum number of changes to pass to a client at a time. '
			. 'Default: 1000', false, true );
	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function getClientWikis( SettingsArray $settings ) {
		$clientWikis = $settings->getSetting( 'localClientDatabases' );

		// make sure we have a mapping from siteId to database name in clientWikis:
		foreach ( $clientWikis as $siteID => $dbName ) {
			if ( is_int( $siteID ) ) {
				unset( $clientWikis[$siteID] );
				$clientWikis[$dbName] = $dbName;
			}
		}

		return $clientWikis;
	}

	/**
	 * Initializes members from command line options and configuration settings.
	 *
	 * @param string[] $clientWikis A mapping of client wiki site IDs to logical database names.
	 * @param ChangeLookup $changeLookup
	 * @param SettingsArray $settings
	 *
	 * @return ChangeDispatcher
	 */
	private function newChangeDispatcher(
		array $clientWikis,
		ChangeLookup $changeLookup,
		SettingsArray $settings
	) {
		$repoID = wfWikiID();
		$repoDB = $settings->getSetting( 'changesDatabase' );
		$batchChunkFactor = $settings->getSetting( 'dispatchBatchChunkFactor' );
		$batchCacheFactor = $settings->getSetting( 'dispatchBatchCacheFactor' );

		$batchSize = (int)$this->getOption( 'batch-size', 1000 );
		$maxChunks = (int)$this->getOption( 'max-chunks', 15 );
		$dispatchInterval = (int)$this->getOption( 'dispatch-interval', 60 );
		$lockGraceInterval = (int)$this->getOption( 'lock-grace-interval', 60 );
		$randomness = (int)$this->getOption( 'randomness', 10 );

		$this->verbose = $this->getOption( 'verbose', false );

		$cacheChunkSize = $batchSize * $batchChunkFactor;
		$cacheSize = $cacheChunkSize * $batchCacheFactor;
		$changesCache = new ChunkCache( $changeLookup, $cacheChunkSize, $cacheSize );
		$reporter = new ObservableMessageReporter();

		$reporter->registerReporterCallback(
			function ( $message ) {
				$this->log( $message );
			}
		);

		$coordinator = new SqlChangeDispatchCoordinator( $repoDB, $repoID );
		$coordinator->setMessageReporter( $reporter );
		$coordinator->setBatchSize( $batchSize );
		$coordinator->setDispatchInterval( $dispatchInterval );
		$coordinator->setLockGraceInterval( $lockGraceInterval );
		$coordinator->setRandomness( $randomness );

		$notificationSender = new JobQueueChangeNotificationSender( $repoDB, $clientWikis );
		$subscriptionLookup = new SqlSubscriptionLookup( wfGetLB() );

		$dispatcher = new ChangeDispatcher(
			$coordinator,
			$notificationSender,
			$changesCache,
			$subscriptionLookup
		);

		$dispatcher->setMessageReporter( $reporter );
		$dispatcher->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );
		$dispatcher->setBatchSize( $batchSize );
		$dispatcher->setMaxChunks( $maxChunks );
		$dispatcher->setBatchChunkFactor( $batchChunkFactor );
		$dispatcher->setVerbose( $this->verbose );

		return $dispatcher;
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
			throw new MWException( "WikibaseLib has not been loaded." );
		}

		$maxTime = (int)$this->getOption( 'max-time', PHP_INT_MAX );
		$maxPasses = (int)$this->getOption( 'max-passes', $maxTime < PHP_INT_MAX ? PHP_INT_MAX : 1 );
		$delay = (int)$this->getOption( 'idle-delay', 10 );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$clientWikis = $this->getClientWikis( $wikibaseRepo->getSettings() );

		if ( empty( $clientWikis ) ) {
			throw new MWException( "No client wikis configured! Please set \$wgWBRepoSettings['localClientDatabases']." );
		}

		$dispatcher = $this->newChangeDispatcher(
			$clientWikis,
			$wikibaseRepo->getStore()->getChangeLookup(),
			$wikibaseRepo->getSettings()
		);

		$dispatcher->getDispatchCoordinator()->initState( $clientWikis );

		$stats = RequestContext::getMain()->getStats();
		$stats->increment( 'wikibase.repo.dispatchChanges.start' );

		$passes = $maxPasses === PHP_INT_MAX ? "unlimited" : $maxPasses;
		$time = $maxTime === PHP_INT_MAX ? "unlimited" : $maxTime;

		$this->log( "Starting loop for $passes passes or $time seconds" );

		$startTime = microtime( true );
		$t = 0;

		// Run passes in a loop, sleeping when idle.
		// Note that idle passes need to be counted to avoid processes staying alive
		// for an indefinite time, potentially leading to a pile up when used with cron.
		for ( $c = 0; $c < $maxPasses; ) {
			if ( $t > $maxTime ) {
				$this->trace( "Reached max time after $t seconds." );
				// timed out
				break;
			}

			$runStartTime = microtime( true );
			$c++;

			try {
				$this->trace( "Picking a client wiki..." );
				$wikiState = $dispatcher->selectClient();

				if ( $wikiState ) {
					$dispatchedChanges = $dispatcher->dispatchTo( $wikiState );
					$stats->updateCount( 'wikibase.repo.dispatchChanges.changes', $dispatchedChanges );
				} else {
					$stats->increment( 'wikibase.repo.dispatchChanges.noclient' );
					// Try again later, unless we have already reached the limit.
					if ( $c < $maxPasses ) {
						$this->trace( "Idle: No client wiki found in need of dispatching. "
							. "Sleeping for {$delay} seconds." );

						sleep( $delay );
					} else {
						$this->trace( "Idle: No client wiki found in need of dispatching. " );
					}
				}
			} catch ( Exception $ex ) {
				$stats->increment( 'wikibase.repo.dispatchChanges.exception' );
				if ( $c < $maxPasses ) {
					$this->log( "ERROR: $ex; sleeping for {$delay} seconds" );
					sleep( $delay );
				} else {
					$this->log( "ERROR: $ex" );
				}
			}

			$t = ( microtime( true ) - $startTime );
			$stats->timing( 'wikibase.repo.dispatchChanges.pass-time', ( microtime() - $runStartTime ) * 1000 );
		}

		$stats->timing( 'wikibase.repo.dispatchChanges.execute-time', $t * 1000 );
		$stats->updateCount( 'wikibase.repo.dispatchChanges.passes', $c );

		$this->log( "Done, exiting after $c passes and $t seconds." );
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

$maintClass = DispatchChanges::class;
require_once RUN_MAINTENANCE_IF_MAIN;
