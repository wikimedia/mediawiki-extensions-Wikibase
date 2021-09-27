<?php

namespace Wikibase\Client\Tests\Integration\Usage\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\Sql\EntityUsageTable;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;

/**
 * @covers \Wikibase\Client\Usage\Sql\EntityUsageTable
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class EntityUsageTableTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = EntityUsageTable::DEFAULT_TABLE_NAME;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return array[]
	 */
	private function getUsageRows( int $pageId, array $usages ) {
		$rows = [];

		foreach ( $usages as $key => $usage ) {
			if ( is_int( $key ) ) {
				$key = $usage->getEntityId()->getSerialization() . '#' . $usage->getAspectKey();
			}

			$rows[$key] = [
				'eu_page_id' => $pageId,
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
				'eu_aspect' => $usage->getAspectKey(),
			];
		}

		return $rows;
	}

	private function getEntityUsageTable( $batchSize = 1000 ) {
		return new EntityUsageTable( new ItemIdParser(), $this->db, $batchSize );
	}

	public function testAddUsages() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		];

		$usagesT2 = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		];

		$usageTable = $this->getEntityUsageTable();

		// adding usages should put them into the database
		$usageTable->addUsages( 23, $usagesT1 );

		$rowsT1 = $this->getUsageRows( 23, $usagesT1 );
		$this->assertUsageTableContains( $rowsT1 );

		$oldRows = $this->getUsageRows( 23, array_diff( $usagesT1, $usagesT2 ) );
		$this->assertUsageTableContains( $oldRows );

		// adding usages that were already tracked should be ignored
		$usageTable->addUsages( 23, $usagesT2 );

		$keepRows = $this->getUsageRows(
			23,
			array_unique( array_merge( $usagesT1, $usagesT2 ) )
		);
		$this->assertUsageTableContains( $keepRows );
	}

	public function testPruneUsages() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$usages = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		];

		$usageTable = $this->getEntityUsageTable();

		// Adding usages should put them into the database
		$usageTable->addUsages( 23, $usages );

		$rows = $this->getUsageRows( 23, $usages );
		$this->assertUsageTableContains( $rows );

		$usageTable->pruneUsages( 23 );

		$this->assertUsageTableDoesNotContain( $rows );
	}

	public function testRemoveUsages() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$usages = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE, 'de' ),
			new EntityUsage( $q3, EntityUsage::OTHER_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		];

		$usageTable = $this->getEntityUsageTable( /* $batchSize = */ 2 );

		// Adding usages should put them into the database
		$usageTable->addUsages( 23, $usages );

		$rows = $this->getUsageRows( 23, $usages );
		$this->assertUsageTableContains( $rows );

		// Test batching by removing more usages than $batchSize
		$usageTable->removeUsages( 23, [ $usages[0], $usages[2], $usages[3] ] );

		$rows = array_values( $rows );
		$this->assertUsageTableDoesNotContain( [ $rows[0], $rows[2], $rows[3] ] );
		$this->assertUsageTableContains( [ $rows[1], $rows[4] ] );
	}

	private function getUsageStrings( array $usages ) {
		$strings = array_map( function ( EntityUsage $usage ) {
			return $usage->getIdentityString();
		}, array_values( $usages ) );

		sort( $strings );
		return $strings;
	}

	public function testQueryUsages() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		];

		$usagesT2 = [
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		];

		$usageTable = $this->getEntityUsageTable();

		$usageTable->addUsages( 23, $usagesT1 );

		// Add for different page, to detect leakage between page ids.
		$usageTable->addUsages( 25, $usagesT2 );

		$this->assertEquals(
			$this->getUsageStrings( $usagesT1 ),
			$this->getUsageStrings( $usageTable->queryUsages( 23 ) )
		);

		$this->assertEquals(
			$this->getUsageStrings( $usagesT2 ),
			$this->getUsageStrings( $usageTable->queryUsages( 25 ) )
		);
	}

	public function testGetQueryUsagesSkipsRowsForEntitiesOfUnknownType() {
		$customEntityId = $this->createMock( EntityId::class );
		$customEntityId->method( 'getSerialization' )
			->willReturn( 'ODD123' );
		$customEntityId->method( 'getEntityType' )
			->willReturn( 'custom-type' );

		$usageTable = $this->getEntityUsageTable();

		$usageTable->addUsages( 100, [ new EntityUsage( new ItemId( 'Q3' ), EntityUsage::ALL_USAGE ) ] );
		$usageTable->addUsages( 100, [ new EntityUsage( $customEntityId, EntityUsage::ALL_USAGE ) ] );

		$usages = $usageTable->queryUsages( 100 );

		$this->assertArrayHasKey( 'Q3#X', $usages );
		$this->assertArrayNotHasKey( 'ODD123#X', $usages );
	}

	public function testGetPagesUsing() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3s = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE );
		$u4t = new EntityUsage( $q4, EntityUsage::TITLE_USAGE );

		$usageTable = $this->getEntityUsageTable( 2 );
		$usageTable->addUsages( 23, [ $u3s, $u3l, $u4l ] );
		$usageTable->addUsages( 42, [ $u4l, $u4t ] );

		$pages = $usageTable->getPagesUsing( [ $q6 ] );
		$this->assertSame( [], iterator_to_array( $pages ) );

		$pages = $usageTable->getPagesUsing( [ $q3 ] );
		$this->assertSamePageEntityUsages(
			[ 23 => new PageEntityUsages( 23, [ $u3s, $u3l ] ) ],
			iterator_to_array( $pages ),
			'Pages using Q3'
		);

		$pages = $usageTable->getPagesUsing( [ $q4, $q3 ], [ EntityUsage::LABEL_USAGE ] );
		$this->assertSamePageEntityUsages(
			[
				23 => new PageEntityUsages( 23, [ $u3l, $u4l ] ),
				42 => new PageEntityUsages( 42, [ $u4l ] ),
			],
			iterator_to_array( $pages ),
			'Pages using "label" on Q4 or Q3'
		);

		$pages = $usageTable->getPagesUsing( [ $q3 ], [ EntityUsage::ALL_USAGE ] );
		$this->assertSame( [], iterator_to_array( $pages ), 'Pages using "all" on Q3' );

		$pages = $usageTable->getPagesUsing( [ $q4 ], [ EntityUsage::SITELINK_USAGE ] );
		$this->assertSame( [], iterator_to_array( $pages ), 'Pages using "sitelinks" on Q4' );

		$pages = $usageTable->getPagesUsing(
			[ $q3, $q4 ],
			[ EntityUsage::TITLE_USAGE, EntityUsage::SITELINK_USAGE ]
		);
		$this->assertCount(
			2,
			iterator_to_array( $pages ),
			'Pages using "title" or "sitelinks" on Q3 or Q4'
		);
	}

	/**
	 * @param PageEntityUsages[] $expected
	 * @param PageEntityUsages[] $actual
	 * @param string $message
	 */
	private function assertSamePageEntityUsages( array $expected, array $actual, $message = '' ) {
		if ( $message !== '' ) {
			$message .= "\n";
		}

		foreach ( $expected as $key => $expectedUsages ) {
			$actualUsages = $actual[$key];

			$this->assertEquals(
				$expectedUsages->getPageId(),
				$actualUsages->getPageId(),
				$message . "[Page $key] " . 'Page ID mismatches!'
			);
			$this->assertEquals(
				$expectedUsages->getUsages(),
				$actualUsages->getUsages(),
				$message . "[Page $key] " . 'Usages:'
			);
		}

		$this->assertSame( [], array_slice( $actual, count( $expected ) ), $message . 'Extra entries found!' );
	}

	public function testGetUnusedEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );
		$q6 = new ItemId( 'Q6' );

		$u3i = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE );

		$usages = [ $u3i, $u3l, $u4l ];

		$usageTable = $this->getEntityUsageTable( 2 );
		$usageTable->addUsages( 23, $usages );

		$this->assertSame( [], $usageTable->getUnusedEntities( [ $q4 ] ), 'Q4 should not be unused' );

		if ( $this->db->getType() === 'mysql' ) {
			// On MySQL we use UNIONs on the table… as the table is temporary that
			// doesn't work in unit tests.
			// https://dev.mysql.com/doc/refman/5.7/en/temporary-table-problems.html
			$entityIds = [ $q6 ];
			$unused = $usageTable->getUnusedEntities( $entityIds );
			$this->assertEquals( [ $q6 ], array_values( $unused ), 'Q6 should be unused' );
		} else {
			$entityIds = [ $q4, $q5, $q6 ];
			$unused = $usageTable->getUnusedEntities( $entityIds );
			$this->assertEquals( [ $q5, $q6 ], array_values( $unused ), 'Q5 and Q6 should be unused' );
		}
	}

	/**
	 * @param array[] $rows
	 */
	private function assertUsageTableContains( array $rows ) {
		foreach ( $rows as $row ) {
			$name = preg_replace( '/\s+/s', ' ', print_r( $row, true ) );
			$this->assertTrue( $this->rowExists( $row ), "Missing row: $name" );
		}
	}

	/**
	 * @param array[] $rows
	 */
	private function assertUsageTableDoesNotContain( array $rows ) {
		foreach ( $rows as $row ) {
			$name = preg_replace( '/\s+/s', ' ', print_r( $row, true ) );
			$this->assertFalse( $this->rowExists( $row ), "Unexpected row: $name" );
		}
	}

	/**
	 * @param array $conditions
	 *
	 * @return bool
	 */
	private function rowExists( array $conditions ) {
		$count = $this->db->selectRowCount( EntityUsageTable::DEFAULT_TABLE_NAME, '*', $conditions );
		return $count > 0;
	}

}
