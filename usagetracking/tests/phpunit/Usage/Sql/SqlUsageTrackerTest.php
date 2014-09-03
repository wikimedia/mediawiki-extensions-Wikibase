<?php
namespace Wikibase\Usage\Tests;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_Assert as Assert;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Usage\Sql\SqlUsageTracker;

/**
 * @covers Wikibase\Usage\Sql\SqlUsageTracker
 *
 * @group Wikibase
 * @group WikibaseUsage
 * @group database
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlUsageTrackerTest extends \MediaWikiTestCase {

	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->tablesUsed[] = 'wbc_entity_usage';
	}

	private function newUsageTracker() {
		return new SqlUsageTracker( new BasicEntityIdParser() );
	}

	public function testUpdateUsageForPage() {
		$tester = new UsageTrackerContractTester( $this->newUsageTracker() );
		$tester->testUpdateUsageForPage();
	}

	public function testGetUsageForPage() {
		$tester = new UsageTrackerContractTester( $this->newUsageTracker() );
		$tester->testGetUsageForPage();
	}

	public function testGetPagesUsing() {
		$tester = new UsageTrackerContractTester( $this->newUsageTracker() );
		$tester->testGetPagesUsing();
	}

	public function testGetUnusedEntities() {
		$tester = new UsageTrackerContractTester( $this->newUsageTracker() );
		$tester->testGetUnusedEntities();
	}

}
