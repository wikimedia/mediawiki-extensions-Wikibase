<?php

namespace Wikibase\Repo\Tests;

use LockManagerGroup;
use Maintenance;
use MediaWiki\MediaWikiServices;
use MWException;
use WANObjectCache;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use Wikibase\Store\ChangeDispatchCoordinator;
use Wikibase\Store\Sql\LockManagerSqlChangeDispatchCoordinator;
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
	 * @var WANObjectCache $cache
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

		$this->createFakeTables();

		$coordinatorOne = $this->createCoordinator( $wikibaseRepo->getSettings() );
		$coordinatorOne->initState( $this->getClientWikis() );

		$number = $this->getOption( 'number', 3 );
		for ( $i = 0; $i < $number; $i++ ) {
			$this->testOneCoordinator( $coordinatorOne );
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
				$settings->getSetting( 'changesDatabase' ),
				$repoID
			);
		} else {
			$coordinator = new SqlChangeDispatchCoordinator(
				$settings->getSetting( 'changesDatabase' ),
				$repoID
			);
		}
		$coordinator->setChangesTable( self::TABLE_PREFIX . 'wb_changes' );
		$coordinator->setStateTable( self::TABLE_PREFIX . 'wb_changes_dispatch' );

		return $coordinator;
	}

	/**
	 * @param ChangeDispatchCoordinator $coordinator
	 */
	private function testOneCoordinator( ChangeDispatchCoordinator $coordinator ) {
		$state = $coordinator->selectClient();
		$clientId = $state['chd_site'];
		if ( !$clientId ) {
			return;
		}
		assert( $this->readTestValue( $clientId ) === 0 );

		sleep( 1 );
		assert( $this->readTestValue( $clientId ) === 0 );
		$this->writeTestValue( $clientId, 1 );

		sleep( 1 );
		assert( $this->readTestValue( $clientId ) === 1 );

		$this->writeTestValue( $clientId, 0 );

		$state['chd_seen'] += 1;
		$coordinator->releaseClient( $state );
	}

	private function readTestValue( $key ) {
		$memcKey = wfMemcKey( 'test-dispatch-coordinator-' . $key );
		$this->log( "Getting cache key $key" );
		return $this->cache->get( $memcKey );
	}

	private function writeTestValue( $key, $value ) {
		$cache = $this->cache;
		$memcKey = wfMemcKey( 'test-dispatch-coordinator-' . $key );
		$this->log( "Setting cache key $key and value $value" );
		$cache->set( $memcKey, $value, $cache::TTL_MINUTE * 15 );
	}

	private function createFakeTables() {
		$dbw = wfGetDB( DB_MASTER );
		$tables = [ 'wb_changes_dispatch', 'wb_changes' ];
		foreach ( $tables as $table ) {
			$dbw->duplicateTableStructure( $table, self::TABLE_PREFIX . $table, true );
		}

		// Inserting a fake change to fool the dispatch coordinator
		$dbw->insert( self::TABLE_PREFIX . 'wb_changes', [ 'change_id' => 1000 ] );
	}

	/**
	 * @param string $message
	 */
	public function log( $message ) {
		$this->output( date( 'H:i:s' ) . '     ' . $message . "\n", 'dispatchChanges::log' );
		$this->cleanupChanneled();
	}

}

$maintClass = TestDispatchCoordinator::class;
require_once RUN_MAINTENANCE_IF_MAIN;
