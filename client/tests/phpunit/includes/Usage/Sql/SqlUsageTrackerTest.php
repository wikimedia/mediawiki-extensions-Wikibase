<?php

namespace Wikibase\Client\Tests\Usage\Sql;

use Wikibase\Client\Store\Sql\ConsistentReadConnectionManager;
use Wikibase\Client\Tests\Usage\UsageLookupContractTester;
use Wikibase\Client\Tests\Usage\UsageTrackerContractTester;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\Client\Usage\Sql\EntityUsageTable;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @covers Wikibase\Client\Usage\Sql\SqlUsageTracker
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlUsageTrackerTest extends \MediaWikiTestCase {

	/**
	 * @var SqlUsageTracker
	 */
	private $sqlUsageTracker;

	/**
	 * @var UsageTrackerContractTester
	 */
	private $trackerTester;

	/**
	 * @var UsageLookupContractTester
	 */
	private $lookupTester;

	protected function setUp() {
		if ( WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'useLegacyUsageIndex' ) ) {
			$this->markTestSkipped( 'Skipping test for SqlUsageTracker, because the useLegacyUsageIndex option is set.' );
		}

		$this->tablesUsed[] = 'wbc_entity_usage';
		$this->tablesUsed[] = 'page';

		parent::setUp();

		$this->sqlUsageTracker = new SqlUsageTracker(
			new BasicEntityIdParser(),
			new ConsistentReadConnectionManager( wfGetLB() )
		);

		$this->trackerTester = new UsageTrackerContractTester( $this->sqlUsageTracker, array( $this, 'getUsages' ) );
		$this->lookupTester = new UsageLookupContractTester( $this->sqlUsageTracker, array( $this, 'putUsages' )  );
	}

	public function getUsages( $pageId, $timestamp ) {
		$db = wfGetDB( DB_SLAVE );
		$updater = new EntityUsageTable( new BasicEntityIdParser(), $db, 'wbc_entity_usage', 1000 );
		return $updater->queryUsages( $pageId, '>=', $timestamp );
	}

	public function putUsages( $pageId, array $usages, $timestamp ) {
		$db = wfGetDB( DB_MASTER );
		$updater = new EntityUsageTable( new BasicEntityIdParser(), $db, 'wbc_entity_usage', 1000 );
		return $updater->addUsages( $pageId, $usages, $timestamp );
	}

	public function testTrackUsedEntities() {
		$this->trackerTester->testTrackUsedEntities();
	}

	public function testRemoveEntities() {
		$this->trackerTester->testRemoveEntities();
	}

	public function testPruneStaleUsages() {
		$this->trackerTester->testPruneStaleUsages();
	}

	public function testGetUsageForPage() {
		$this->lookupTester->testGetUsageForPage();
	}

	public function testGetPagesUsing() {
		$this->lookupTester->testGetPagesUsing();
	}

	public function testGetUnusedEntities() {
		$this->lookupTester->testGetUnusedEntities();
	}

}
