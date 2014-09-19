<?php
namespace Wikibase\Client\Tests\Usage\Sql;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_Assert as Assert;
use Wikibase\Client\Tests\Usage\UsageLookupContractTester;
use Wikibase\Client\Tests\Usage\UsageTrackerContractTester;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Usage\Sql\SqlUsageTracker
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group database
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

	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->tablesUsed[] = 'wbc_entity_usage';
	}

	public function setUp() {
		parent::setUp();

		$this->sqlUsageTracker = new SqlUsageTracker( new BasicEntityIdParser(), wfGetLB() );

		$this->trackerTester = new UsageTrackerContractTester( $this->sqlUsageTracker );
		$this->lookupTester = new UsageLookupContractTester( $this->sqlUsageTracker, $this->sqlUsageTracker );
	}

	public function testTrackUsedEntities() {
		$this->trackerTester->testTrackUsedEntities();
	}

	public function testRemoveEntities() {
		$this->trackerTester->testRemoveEntities();
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

	private function makeUsages( $n ) {
		$usages = array();

		for ( $i = 1; $i <= $n; $i++ ) {
			$key = "Q$i";
			$id = new ItemId( $key );

			$usages["$key#L"] = new EntityUsage( $id, EntityUsage::LABEL_USAGE );
			$usages["$key#P"] = new EntityUsage( $id, EntityUsage::PAGE_USAGE );
		}

		return $usages;
	}

	private function makeItemIds( $n ) {
		$ids = array();

		for ( $i = 1; $i <= $n; $i++ ) {
			$key = "Q$i";
			$ids[$key]= new ItemId( $key );
		}

		return $ids;
	}

	public function testTrackUsedEntities_batching() {
		$usages = $this->makeUsages( 10 );

		$this->sqlUsageTracker->setBatchSize( 3 );
		$this->sqlUsageTracker->trackUsedEntities( 7, $usages );

		// inserting more rows than fit into a single batch
		$actual = $this->sqlUsageTracker->getUsageForPage( 7 );
		$this->trackerTester->assertSameUsages( $usages, $actual );

		// removing more rows than fit into a single batch
		$this->sqlUsageTracker->trackUsedEntities( 7, array() );
		$actual = $this->sqlUsageTracker->getUsageForPage( 7 );
		$this->assertEquals( array(), $actual );
	}

	public function testRemoveEntities_batching() {
		$usages = $this->makeUsages( 10 );
		$this->sqlUsageTracker->trackUsedEntities( 7, $usages );

		// removing more rows than fit into a single batch
		$entities = $this->makeItemIds( 5 );
		$this->sqlUsageTracker->setBatchSize( 3 );
		$this->sqlUsageTracker->removeEntities( $entities );

		$pages = $this->sqlUsageTracker->getPagesUsing( $entities );
		$this->assertEquals( array(), iterator_to_array( $pages ) );
	}

}
