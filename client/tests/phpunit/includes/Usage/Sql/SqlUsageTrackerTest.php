<?php

namespace Wikibase\Client\Tests\Usage\Sql;

use Wikibase\Client\Store\Sql\ConsistentReadConnectionManager;
use Wikibase\Client\Tests\Usage\UsageLookupContractTester;
use Wikibase\Client\Tests\Usage\UsageTrackerContractTester;
use Wikibase\Client\Usage\Sql\EntityUsageTable;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @covers Wikibase\Client\Usage\Sql\SqlUsageTracker
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0+
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
		$this->tablesUsed[] = EntityUsageTable::DEFAULT_TABLE_NAME;
		$this->tablesUsed[] = 'page';

		parent::setUp();

		$this->sqlUsageTracker = new SqlUsageTracker(
			new BasicEntityIdParser(),
			new ConsistentReadConnectionManager( wfGetLB() )
		);

		$this->trackerTester = new UsageTrackerContractTester( $this->sqlUsageTracker, array( $this, 'getUsages' ) );
		$this->lookupTester = new UsageLookupContractTester( $this->sqlUsageTracker, array( $this, 'putUsages' ) );
	}

	public function getUsages( $pageId ) {
		$db = wfGetDB( DB_SLAVE );
		$updater = new EntityUsageTable( new BasicEntityIdParser(), $db );
		return $updater->queryUsages( $pageId );
	}

	public function putUsages( $pageId, array $usages ) {
		$db = wfGetDB( DB_MASTER );
		$updater = new EntityUsageTable( new BasicEntityIdParser(), $db );
		return $updater->addUsages( $pageId, $usages );
	}

	public function testAddUsedEntities() {
		$this->trackerTester->testAddUsedEntities();
	}

	public function testReplaceUsedEntities() {
		$this->trackerTester->testReplaceUsedEntities();
	}

	public function testPruneUsages() {
		$this->trackerTester->testPruneUsages();
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
