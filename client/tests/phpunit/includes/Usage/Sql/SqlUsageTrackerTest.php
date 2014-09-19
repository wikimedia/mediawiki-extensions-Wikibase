<?php
namespace Wikibase\Client\Tests\Usage\Sql;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_Assert as Assert;
use Wikibase\Client\Tests\Usage\UsageLookupContractTester;
use Wikibase\Client\Tests\Usage\UsageTrackerContractTester;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;

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

		$sqlUsageTracker = new SqlUsageTracker( new BasicEntityIdParser(), wfGetLB() );

		$this->trackerTester = new UsageTrackerContractTester( $sqlUsageTracker );
		$this->lookupTester = new UsageLookupContractTester( $sqlUsageTracker, $sqlUsageTracker );
	}

	public function testTrackUsedEntities() {
		$this->trackerTester->testTrackUsedEntities();
	}

	public function testRemoverEntities() {
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

}
