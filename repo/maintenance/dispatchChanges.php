<?php

namespace Wikibase;

use Exception;
use LockManagerGroup;
use Maintenance;
use MediaWiki\MediaWikiServices;
use MWException;
use MWExceptionHandler;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Lib\Store\ChunkCache;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Repo\ChangeDispatcher;
use Wikibase\Repo\Notifications\JobQueueChangeNotificationSender;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Store\Sql\LockManagerSqlChangeDispatchCoordinator;
use Wikibase\Store\Sql\SqlChangeDispatchCoordinator;
use Wikibase\Store\Sql\SqlSubscriptionLookup;
use Wikimedia\Assert\Assert;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that polls for Wikibase changes in the shared wb_changes table
 * and dispatches the relevant changes to any client wikis' job queues.
 *
 * @license GPL-2.0-or-later
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
			"Maintenance script that polls for Wikibase changes in the shared wb_changes table\n" .
			"and dispatches them to any client wikis using their job queue.\n" .
			"See docs/change-propagation.wiki for an overview of the change propagation mechanism."
		);

		$this->addOption( 'verbose', "Report activity." );
		$this->addOption( 'idle-delay', "Seconds to sleep when idle. Default: 10", false, true );
		$this->addOption( 'dispatch-interval', "How often to dispatch to each target wiki. "
					. "Default: every 60 seconds", false, true );
		$this->addOption( 'randomness', "Number of least current target wikis to pick from at random. "
					. "Default: 15.", false, true );
		$this->addOption( 'max-passes', "The number of passes to perform. "
					. "Default: 1 if --max-time is not set, infinite if it is.", false, true );
		$this->addOption( 'max-time', "The number of seconds to run before exiting, "
					. "if --max-passes is not reached. Default: Set in LocalSettings ('dispatchMaxTime').", false, true );
		$this->addOption( 'max-chunks', 'Maximum number of chunks or passes per wiki when '
			. 'selecting pending changes. Default: 15', false, true );
		$this->addOption( 'batch-size', 'Maximum number of changes to pass to a client at a time. '
			. 'Default: 1000', false, true );
		$this->addOption( 'client', 'Only dispatch to the client with this site IDs. '
			. 'May be specified multiple times to select several clients.',
			false, true, false, true );
	}

	/**
	 * @param string[] $clientWikis as defined in the localClientDatabases config setting.
	 *
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function getClientWikis( array $clientWikis ) {
		Assert::parameterElementType( 'string', $clientWikis, '$clientWikis' );

		// make sure we have a mapping from siteId to database name in clientWikis:
		foreach ( $clientWikis as $siteID => $dbName ) {
			if ( is_int( $siteID ) ) {
				unset( $clientWikis[$siteID] );
				$siteID = $dbName;
			}
			$clientWikis[$siteID] = $dbName;
		}

		// If this repo is also a client, make sure it dispatches also to itself.
		if ( WikibaseSettings::isClientEnabled() ) {
			$clientSettings = WikibaseSettings::getClientSettings();
			$repoName = $clientSettings->getSetting( 'repoSiteId' );
			$repoDb = $clientSettings->getSetting( 'repoDatabase' );

			if ( $repoDb === false ) {
				$repoDb = MediaWikiServices::getInstance()->getMainConfig()->get( 'DBname' );
			}

			if ( !isset( $clientWikis[$repoName] ) ) {
				$clientWikis[$repoName] = $repoDb;
			}
		}

		return $clientWikis;
	}

	/**
	 * @param string[] $allClientWikis as returned by getClientWikis().
	 * @param string[]|null $selectedSiteIDs site IDs to select, or null to disable filtering.
	 *
	 * @throws MWException
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function filterClientWikis( array $allClientWikis, array $selectedSiteIDs = null ) {
		Assert::parameterElementType( 'string', $allClientWikis, '$allClientWikis' );

		if ( $selectedSiteIDs === null ) {
			return $allClientWikis;
		}
		Assert::parameterElementType( 'string', $selectedSiteIDs, '$selectedSiteIDs' );

		$clientWikis = [];
		foreach ( $selectedSiteIDs as $siteID ) {
			if ( array_key_exists( $siteID, $allClientWikis ) ) {
				$clientWikis[$siteID] = $allClientWikis[$siteID];
			} else {
				throw new MWException(
					"No client wiki with site ID $siteID configured! " .
					"Please check \$wgWBRepoSettings['localClientDatabases']."
				);
			}
		}

		return $clientWikis;
	}

	/**
	 * Initializes members from command line options and configuration settings.
	 *
	 * @param string[] $clientWikis A mapping of client wiki site IDs to logical database names.
	 * @param EntityChangeLookup $changeLookup
	 * @param SettingsArray $settings
	 *
	 * @return ChangeDispatcher
	 */
	private function newChangeDispatcher(
		array $clientWikis,
		EntityChangeLookup $changeLookup,
		SettingsArray $settings
	) {
		$repoDB = $settings->getSetting( 'changesDatabase' );
		$batchChunkFactor = $settings->getSetting( 'dispatchBatchChunkFactor' );
		$batchCacheFactor = $settings->getSetting( 'dispatchBatchCacheFactor' );

		$batchSize = (int)$this->getOption(
			'batch-size',
			$settings->getSetting( 'dispatchDefaultBatchSize' )
		);
		$maxChunks = (int)$this->getOption(
			'max-chunks',
			$settings->getSetting( 'dispatchDefaultMaxChunks' )
		);
		$dispatchInterval = (int)$this->getOption(
			'dispatch-interval',
			$settings->getSetting( 'dispatchDefaultDispatchInterval' )
		);
		$randomness = (int)$this->getOption(
			'randomness',
			$settings->getSetting( 'dispatchDefaultDispatchRandomness' )
		);

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

		$coordinator = $this->getCoordinator( $settings );
		$coordinator->setMessageReporter( $reporter );
		$coordinator->setBatchSize( $batchSize );
		$coordinator->setDispatchInterval( $dispatchInterval );
		$coordinator->setRandomness( $randomness );

		$notificationSender = new JobQueueChangeNotificationSender( $repoDB, $clientWikis );
		$subscriptionLookup = new SqlSubscriptionLookup(
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);

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

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$defaultMaxTime = $wikibaseRepo->getSettings()->getSetting( 'dispatchMaxTime' );

		if ( $defaultMaxTime == 0 ) {
			$this->log( 'dispatchMaxTime 0, so exiting early and not performing dispatch operations.' );
			return;
		}

		$maxTime = (int)$this->getOption( 'max-time', $defaultMaxTime );
		$maxPasses = (int)$this->getOption( 'max-passes', $maxTime < PHP_INT_MAX ? PHP_INT_MAX : 1 );
		$delay = (int)$this->getOption( 'idle-delay', $wikibaseRepo->getSettings()->getSetting( 'dispatchIdleDelay' ) );
		$selectedClients = $this->getOption( 'client' );

		$clientWikis = $this->getClientWikis(
			$wikibaseRepo->getSettings()->getSetting( 'localClientDatabases' )
		);

		if ( empty( $clientWikis ) ) {
			throw new MWException( "No client wikis configured! Please set \$wgWBRepoSettings['localClientDatabases']." );
		}

		$clientWikis = $this->filterClientWikis( $clientWikis, $selectedClients );

		if ( empty( $clientWikis ) ) {
			throw new MWException( 'No client wikis selected!' );
		}

		$dispatcher = $this->newChangeDispatcher(
			$clientWikis,
			$wikibaseRepo->getStore()->getEntityChangeLookup(),
			$wikibaseRepo->getSettings()
		);

		$dispatcher->getDispatchCoordinator()->initState( $clientWikis );

		$stats = MediaWikiServices::getInstance()->getPerDbNameStatsdDataFactory();
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

			$wikiState = null;
			$passStartTime = microtime( true );
			$c++;

			try {
				$this->trace( "Picking a client wiki..." );
				$selectClientStartTime = microtime( true );
				$wikiState = $dispatcher->selectClient();
				$stats->timing(
					'wikibase.repo.dispatchChanges.selectClient-time',
					( microtime( true ) - $selectClientStartTime ) * 1000
				);

				if ( $wikiState ) {
					$dispatchedChanges = $dispatcher->dispatchTo( $wikiState );
					$stats->updateCount( 'wikibase.repo.dispatchChanges.changes', $dispatchedChanges );
					$stats->updateCount( 'wikibase.repo.dispatchChanges.changes-per-client.'
											. $wikiState['chd_site'], $dispatchedChanges );
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

				MWExceptionHandler::logException( $ex );

				if ( $c < $maxPasses ) {
					$this->log( "ERROR: $ex; sleeping for {$delay} seconds" );
					sleep( $delay );
				} else {
					$this->log( "ERROR: $ex" );
				}
				if ( $wikiState ) {
					$dispatcher->getDispatchCoordinator()->releaseClient( $wikiState );
				}
			}

			$t = ( microtime( true ) - $startTime );
			$stats->timing( 'wikibase.repo.dispatchChanges.pass-time', ( microtime( true ) - $passStartTime ) * 1000 );
		}

		$stats->timing( 'wikibase.repo.dispatchChanges.execute-time', $t * 1000 );
		$stats->updateCount( 'wikibase.repo.dispatchChanges.passes', $c );

		$this->log( "Done, exiting after $c passes and $t seconds." );
	}

	/**
	 * Find and return the proper ChangeDispatchCoordinator
	 *
	 * @param SettingsArray $settings
	 *
	 * @return SqlChangeDispatchCoordinator
	 */
	private function getCoordinator( SettingsArray $settings ) {
		$repoID = wfWikiID();
		$lockManagerName = $settings->getSetting( 'dispatchingLockManager' );
		$LBFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		if ( !is_null( $lockManagerName ) ) {
			$lockManager = LockManagerGroup::singleton( wfWikiID() )->get( $lockManagerName );
			return new LockManagerSqlChangeDispatchCoordinator(
				$lockManager,
				$LBFactory,
				$settings->getSetting( 'changesDatabase' ),
				$repoID
			);
		} else {
			return new SqlChangeDispatchCoordinator(
				$settings->getSetting( 'changesDatabase' ),
				$repoID,
				$LBFactory
			);
		}
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
