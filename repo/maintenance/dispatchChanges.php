<?php

namespace Wikibase;

use Exception;
use Maintenance;
use MWException;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Lib\Store\SiteLinkTable;
use Wikibase\Repo\ChangeDispatcher;
use Wikibase\Repo\Notifications\JobQueueChangeNotificationSender;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\DualSubscriptionLookup;
use Wikibase\Store\SiteLinkSubscriptionLookup;
use Wikibase\Store\Sql\SqlChangeDispatchCoordinator;
use Wikibase\Store\Sql\SqlSubscriptionLookup;
use Wikibase\Store\SubscriptionLookup;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that polls for Wikibase changes in the shared wb_changes table
 * and dispatches the relevant changes to any client wikis' job queues.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchChanges extends Maintenance {

	/**
	 * @var bool
	 */
	private $verbose;

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
					. "Default: 10.", false, true );
		$this->addOption( 'max-passes', "The number of passes to perform. "
					. "Default: 1 if --max-time is not set, infinite if it is.", false, true );
		$this->addOption( 'max-time', "The number of seconds to run before exiting, "
					. "if --max-passes is not reached. Default: infinite.", false, true );
		$this->addOption( 'batch-size', "Max number of changes to pass to a client at a time. "
					. "Default: 1000", false, true );
	}

	/**
	 * Initializes members from command line options and configuration settings.
	 *
	 * @param SettingsArray $settings
	 *
	 * @return ChangeDispatcher
	 * @throws MWException
	 */
	private function newChangeDispatcher( SettingsArray $settings ) {
		$changesTable = new ChangesTable(); //TODO: allow injection of a mock instance for testing

		$repoDB = $settings->getSetting( 'changesDatabase' );
		$clientWikis = $settings->getSetting( 'localClientDatabases' );
		$batchChunkFactor = $settings->getSetting( 'dispatchBatchChunkFactor' );
		$batchCacheFactor = $settings->getSetting( 'dispatchBatchCacheFactor' );
		$subscriptionLookupMode = $settings->getSetting( 'subscriptionLookupMode' );

		$batchSize = (int)$this->getOption( 'batch-size', 1000 );
		$dispatchInterval = (int)$this->getOption( 'dispatch-interval', 60 );
		$lockGraceInterval = (int)$this->getOption( 'lock-grace-interval', 60 );
		$randomness = (int)$this->getOption( 'randomness', 10 );

		$this->verbose = $this->getOption( 'verbose', false );

		$cacheChunkSize = $batchSize * $batchChunkFactor;
		$cacheSize = $cacheChunkSize * $batchCacheFactor;
		$changesCache = new ChunkCache( $changesTable, $cacheChunkSize, $cacheSize );

		// make sure we have a mapping from siteId to database name in clientWikis:
		foreach ( $clientWikis as $siteID => $dbName ) {
			if ( is_int( $siteID ) ) {
				unset( $clientWikis[$siteID] );
				$clientWikis[$dbName] = $dbName;
			}
		}

		if ( empty( $clientWikis ) ) {
			throw new MWException( "No client wikis configured! Please set \$wgWBRepoSettings['localClientDatabases']." );
		}

		$reporter = new ObservableMessageReporter();

		$self = $this; // PHP 5.3...
		$reporter->registerReporterCallback(
			function ( $message ) use ( $self ) {
				$self->log( $message );
			}
		);

		$coordinator = new SqlChangeDispatchCoordinator( $repoDB, $clientWikis );
		$coordinator->setMessageReporter( $reporter );
		$coordinator->setBatchSize( $batchSize );
		$coordinator->setDispatchInterval( $dispatchInterval );
		$coordinator->setLockGraceInterval( $lockGraceInterval );
		$coordinator->setRandomness( $randomness );

		$notificationSender = new JobQueueChangeNotificationSender( $repoDB, $clientWikis );
		$subscriptionLookup = $this->getSubscriptionLookup( $repoDB, $subscriptionLookupMode );

		$dispatcher = new ChangeDispatcher(
			$coordinator,
			$notificationSender,
			$changesCache,
			$subscriptionLookup
		);

		$dispatcher->setMessageReporter( $reporter );
		$dispatcher->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );
		$dispatcher->setBatchSize( $batchSize );
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

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$dispatcher = $this->newChangeDispatcher( $settings );

		$dispatcher->getDispatchCoordinator()->initState();

		$passes = $maxPasses === PHP_INT_MAX ? "unlimited" : $maxPasses;
		$time = $maxTime === PHP_INT_MAX ? "unlimited" : $maxTime;

		$this->log( "Starting loop for $passes passes or $time seconds" );

		$startTime = time();
		$t = 0;

		// Run passes in a loop, sleeping when idle.
		// Note that idle passes need to be counted to avoid processes staying alive
		// for an indefinite time, potentially leading to a pile up when used with cron.
		for ( $c = 0; $c < $maxPasses; ) {
			if ( $t  > $maxTime ) {
				$this->trace( "Reached max time after $t seconds." );
				// timed out
				break;
			}

			$c++;

			try {
				$this->trace( "Picking a client wiki..." );
				$wikiState = $dispatcher->selectClient();

				if ( $wikiState ) {
					$dispatcher->dispatchTo( $wikiState  );
				} else {
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
				if ( $c < $maxPasses ) {
					$this->log( "ERROR: $ex; sleeping for {$delay} seconds" );
					sleep( $delay );
				} else {
					$this->log( "ERROR: $ex" );
				}
			}

			$t = ( time() - $startTime );
		}

		$this->log( "Done, exiting after $c passes and $t seconds." );
	}

	/**
	 * @param $repoDB
	 * @param $subscriptionLookupMode
	 *
	 * @return SubscriptionLookup
	 */
	private function getSubscriptionLookup( $repoDB, $subscriptionLookupMode ) {
		$lookup = null;
		$siteLinkSubscriptionLookup = null;
		$sqlSubscriptionLookup = null;

		if ( $subscriptionLookupMode === 'sitelinks'
			|| $subscriptionLookupMode === 'subscriptions+sitelinks'
		) {
			$this->log( "Using sitelinks to target notifications." );
			$siteLinkTable = new SiteLinkTable( 'wb_items_per_site', true, $repoDB );
			$lookup = $siteLinkSubscriptionLookup = new SiteLinkSubscriptionLookup( $siteLinkTable );
		}

		if ( $subscriptionLookupMode === 'subscriptions'
			|| $subscriptionLookupMode === 'subscriptions+sitelinks'
		) {
			$this->log( "Using subscriptions to target notifications." );
			$lookup = $sqlSubscriptionLookup = new SqlSubscriptionLookup( wfGetLB() );
		}

		if ( $siteLinkSubscriptionLookup && $sqlSubscriptionLookup ) {
			$lookup = new DualSubscriptionLookup( $siteLinkSubscriptionLookup, $sqlSubscriptionLookup );
		}

		return $lookup;
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
