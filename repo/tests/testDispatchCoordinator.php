<?php

namespace Wikibase\Repo\Tests;

use LockManagerGroup;
use LogicException;
use Maintenance;
use MediaWiki\MediaWikiServices;
use MWException;
use RuntimeException;
use WANObjectCache;
use Wikibase\Repo\Store\Sql\LockManagerSqlChangeDispatchCoordinator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use Wikibase\Store\ChangeDispatchCoordinator;
use Wikibase\Store\Sql\SqlChangeDispatchCoordinator;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Test maintenance script that runs two dispatching and checks if they conflict.
 * The main purpose of this file is to test dispatchChanges.php maintenance script.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class TestDispatchCoordinator extends Maintenance {

	/**
	 * @var WANObjectCache
	 */
	private $cache;

	const TABLE_PREFIX = 'fake_';

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			"Test maintenance script that runs two dispatching and checks if they conflict.\n" .
			"See docs/change-propagation.wiki for an overview of the change propagation mechanism."
		);
		$this->addOption( 'lock', 'Name of the lock manager to test', false, true, 'l' );
		$this->addOption( 'number', 'Number of tries for a lock manager', false, true, 'n' );
	}

	/**
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function getClientWikis() {
		return [ 'test1wiki' => 'test1wiki1', 'test2wiki' => 'test2wiki' ];
	}

	/**
	 * Maintenance script entry point.
	 */
	public function execute() {
		if ( !defined( 'WBL_VERSION' ) ) {
			throw new MWException( "WikibaseLib has not been loaded." );
		}

		$this->cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$iterations = (int)$this->getOption( 'number', 30 );
		$this->createFakeTables( $iterations );

		$coordinatorOne = $this->createCoordinator( $wikibaseRepo->getSettings() );
		$coordinatorOne->initState( $this->getClientWikis() );

		for ( $i = 1; $i <= $iterations; $i++ ) {
			$this->log( "--- Pass $i of $iterations ---" );
			$this->testCoordinator( $coordinatorOne );
		}
	}

	/**
	 * Find and return the proper ChangeDispatchCoordinator
	 *
	 * @param SettingsArray $settings
	 *
	 * @return ChangeDispatchCoordinator
	 */
	private function createCoordinator( SettingsArray $settings ) {
		$repoID = wfWikiID();
		$lockManagerName = $this->getOption(
			'lock',
			$settings->getSetting( 'dispatchingLockManager' )
		);
		if ( !is_null( $lockManagerName ) ) {
			$lockManager = LockManagerGroup::singleton( wfWikiID() )->get( $lockManagerName );
			$coordinator = new LockManagerSqlChangeDispatchCoordinator(
				$lockManager,
				MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
				$settings->getSetting( 'changesDatabase' ),
				$repoID
			);
		} else {
			$coordinator = new SqlChangeDispatchCoordinator(
				$settings->getSetting( 'changesDatabase' ),
				$repoID,
				MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
			);
		}
		$coordinator->setChangesTable( self::TABLE_PREFIX . 'wb_changes' );
		$coordinator->setStateTable( self::TABLE_PREFIX . 'wb_changes_dispatch' );
		$coordinator->setDispatchInterval( 2 );

		return $coordinator;
	}

	private function testCoordinator( ChangeDispatchCoordinator $coordinator ) {
		$clientId = null;
		$state = null;

		while ( true ) {
			$this->log( "Trying to get a lock on a client wiki..." );
			$state = $coordinator->selectClient();

			if ( $state ) {
				$clientId = $state['chd_site'];
				$this->log( "Got a lock on clinet wiki $clientId" );
				break;
			} else {
				$this->log( "Could not get a lock on any client wiki. Sleep and retry." );
				sleep( 1 );
			}
		};

		$this->writeTestValue( $clientId, 0 );

		sleep( 1 );
		$this->assertSame( 0, $this->readTestValue( $clientId ) );
		$this->writeTestValue( $clientId, 1 );

		sleep( 1 );
		$this->assertSame( 1, $this->readTestValue( $clientId ) );

		$state['chd_seen'] += 1;
		$coordinator->releaseClient( $state );
	}

	private function readTestValue( $key ) {
		$memcKey = wfMemcKey( 'test-dispatch-coordinator-' . $key );
		$value = $this->cache->get( $memcKey );
		if ( $value === null ) {
			throw new RuntimeException( "Caching mechanism doesn't work as expected." );
		}
		$this->log( "Got cache key $key: $value" );
		return is_string( $value ) ? (int)$value : $value;
	}

	private function writeTestValue( $key, $value ) {
		$memcKey = wfMemcKey( 'test-dispatch-coordinator-' . $key );
		$this->cache->set( $memcKey, $value, WANObjectCache::TTL_MINUTE * 15 );
		$this->log( "Set cache key $key to $value" );
	}

	private function createFakeTables( $maxIter ) {
		$dbw = wfGetDB( DB_MASTER );
		$tables = [ 'wb_changes_dispatch', 'wb_changes' ];
		foreach ( $tables as $table ) {
			$dbw->duplicateTableStructure( $table, self::TABLE_PREFIX . $table, true );
		}

		// Inserting a fake change to fool the dispatch coordinator
		// Since we count up chd_seen in testCoordinator(), change_id has to be greater than
		// the number of times we run the test.
		$dbw->insert( self::TABLE_PREFIX . 'wb_changes', [ 'change_id' => $maxIter + 10 ] );
	}

	/**
	 * @param string $message
	 */
	public function log( $message ) {
		$this->output( date( 'H:i:s' ) . '     ' . $message . "\n", 'dispatchChanges::log' );
		$this->cleanupChanneled();
	}

	private function assertSame( $expected, $actual ) {
		if ( $expected !== $actual ) {
			throw new LogicException( "Test failed: $actual is not the same as expected $expected" );
		}
	}

}

$maintClass = TestDispatchCoordinator::class;
require_once RUN_MAINTENANCE_IF_MAIN;
