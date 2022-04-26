<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Usage\Sql;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Tests\Integration\Usage\UsageLookupContractTester;
use Wikibase\Client\Tests\Integration\Usage\UsageTrackerContractTester;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\Sql\EntityUsageTable;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;

/**
 * @covers \Wikibase\Client\Usage\Sql\SqlUsageTracker
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlUsageTrackerTest extends MediaWikiIntegrationTestCase {

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

	protected function setUp(): void {
		$this->tablesUsed[] = EntityUsageTable::DEFAULT_TABLE_NAME;
		$this->tablesUsed[] = 'page';

		parent::setUp();

		$this->sqlUsageTracker = new SqlUsageTracker(
			new ItemIdParser(),
			new SessionConsistentConnectionManager(
				MediaWikiServices::getInstance()->getDBLoadBalancer()
			),
			[],
			100
		);

		$this->trackerTester = new UsageTrackerContractTester( $this->sqlUsageTracker, [ $this, 'getUsages' ] );
		$this->lookupTester = new UsageLookupContractTester( $this->sqlUsageTracker, [ $this, 'putUsages' ] );
	}

	public function getUsages( int $pageId ): array {
		$updater = new EntityUsageTable( new ItemIdParser(), $this->db );
		return $updater->queryUsages( $pageId );
	}

	public function putUsages( int $pageId, array $usages ): int {
		$updater = new EntityUsageTable( new ItemIdParser(), $this->db );
		return $updater->addUsages( $pageId, $usages );
	}

	public function testAddUsedEntities() {
		$this->trackerTester->testAddUsedEntities();
	}

	public function testAddUsedEntitiesDisabledAspects() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usages = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::STATEMENT_USAGE, 'P12' ),
			new EntityUsage( $q3, EntityUsage::DESCRIPTION_USAGE, 'es' ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE, 'de' ),
			new EntityUsage( $q4, EntityUsage::OTHER_USAGE ),
			new EntityUsage( $q5, EntityUsage::OTHER_USAGE ),
			new EntityUsage( $q5, EntityUsage::DESCRIPTION_USAGE, 'ru' ),
		];

		$sqlUsageTracker = new SqlUsageTracker(
			new ItemIdParser(),
			new SessionConsistentConnectionManager(
				MediaWikiServices::getInstance()->getDBLoadBalancer()
			),
			[ EntityUsage::STATEMENT_USAGE, EntityUsage::DESCRIPTION_USAGE =>
				EntityUsage::OTHER_USAGE ],
			100
		);
		$sqlUsageTracker->addUsedEntities( 23, $usages );

		// All entries but the disabled ones should be set
		$this->assertEquals(
			[ 'Q3#S', 'Q3#O', 'Q4#L.de', 'Q4#O', 'Q5#O' ],
			array_keys( $this->getUsages( 23 ) )
		);
	}

	public function testReplaceUsedEntities() {
		$this->trackerTester->testReplaceUsedEntities();
	}

	public function testReplaceUsedEntitiesWithDisabledUsageAspects() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$usages = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::STATEMENT_USAGE, 'P12' ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE, 'de' ),
		];

		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$sqlUsageTracker = new SqlUsageTracker(
			new ItemIdParser(),
			new SessionConsistentConnectionManager( $lb ),
			[],
			100
		);
		// Make sure the disabled entries are actually removed.
		$sqlUsageTracker->addUsedEntities( 23, $usages );

		$sqlUsageTrackerWithDisabledUsageAspects = new SqlUsageTracker(
			new ItemIdParser(),
			new SessionConsistentConnectionManager( $lb ),
			[ EntityUsage::STATEMENT_USAGE ],
			100
		);
		$sqlUsageTrackerWithDisabledUsageAspects->replaceUsedEntities( 23, $usages );

		// All entries but the disabled ones should be set
		$this->assertEquals(
			[ 'Q3#S', 'Q4#L.de' ],
			array_keys( $this->getUsages( 23 ) )
		);
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
		$this->lookupTester->testGetUnusedEntities( $this->db );
	}

}
