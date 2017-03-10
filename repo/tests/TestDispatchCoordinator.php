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

	public function __construct() {
		parent::__construct();

		$this->cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		$this->addDescription(
			"Test maintenance script that runs two dispatching and checks if they conflict.\n" .
			"See docs/change-propagation.wiki for an overview of the change propagation mechanism."
		);

	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function getClientWikis( SettingsArray $settings ) {
		$clientWikis = $settings->getSetting( 'localClientDatabases' );
		foreach ( $clientWikis as $siteID => $dbName ) {
			if ( is_int( $siteID ) ) {
				unset( $clientWikis[$siteID] );
				$clientWikis[$dbName] = $dbName;
			}
		}

		return $clientWikis;
	}

	/**
	 * Maintenance script entry point.
	 */
	public function execute() {
		if ( !defined( 'WBL_VERSION' ) ) {
			throw new MWException( "WikibaseLib has not been loaded." );
		}

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$clientWikis = $this->getClientWikis( $wikibaseRepo->getSettings() );

		if ( empty( $clientWikis ) ) {
			throw new MWException( "No client wikis configured! Please set \$wgWBRepoSettings['localClientDatabases']." );
		}

		// Two coordinators running at the same time
		$this->testOneCoordinator( $this->createCoordinator( $wikibaseRepo->getSettings() ) );
		$this->testOneCoordinator( $this->createCoordinator( $wikibaseRepo->getSettings() ) );
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
		$lockManagerName = $settings->getSetting( 'dispatchingLockManager' );
		if ( !is_null( $lockManagerName ) ) {
			$lockManager = LockManagerGroup::singleton( wfWikiID() )->get( $lockManagerName );
			return new LockManagerSqlChangeDispatchCoordinator(
				$lockManager,
				$settings->getSetting( 'changesDatabase' ),
				$repoID
			);
		} else {
			return new SqlChangeDispatchCoordinator(
				$settings->getSetting( 'changesDatabase' ),
				$repoID
			);
		}
	}

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
