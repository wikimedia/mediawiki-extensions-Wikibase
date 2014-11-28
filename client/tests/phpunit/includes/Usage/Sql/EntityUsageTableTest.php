<?php

namespace Wikibase\Client\Tests\Usage\Sql;

use DatabaseBase;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\Sql\EntityUsageTable;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Usage\Sql\EntityUsageTable
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityUsageTableTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		if ( WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'useLegacyUsageIndex' ) ) {
			$this->markTestSkipped( 'Skipping test for EntityUsageTable, because the useLegacyUsageIndex option is set.' );
		}

		$this->tablesUsed[] = SqlUsageTracker::DEFAULT_TABLE_NAME;
	}

	private function makeUsages( $n ) {
		$usages = array();

		for ( $i = 1; $i <= $n; $i++ ) {
			$key = "Q$i";
			$id = new ItemId( $key );

			$usages["$key#L.de"] = new EntityUsage( $id, EntityUsage::LABEL_USAGE, 'de' );
			$usages["$key#L.en"] = new EntityUsage( $id, EntityUsage::LABEL_USAGE, 'en' );
			$usages["$key#T"] = new EntityUsage( $id, EntityUsage::TITLE_USAGE );
		}

		return $usages;
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return array
	 */
	private function getItemIds( array $usages ) {
		$ids = array();

		foreach ( $usages as $usage ) {
			$id = $usage->getEntityId();

			$key = $id->getSerialization();
			$ids[$key]= $id;
		}

		return $ids;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string $touched timestamp
	 *
	 * @return array[]
	 */
	private function getUsageRows( $pageId, array $usages, $touched ) {
		$rows = array();

		foreach ( $usages as $key => $usage ) {
			$row = array(
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
				'eu_aspect' => $usage->getAspectKey()
			);

			if ( $pageId > 0 ) {
				$row['eu_page_id'] = $pageId;
			}

			if ( $touched !== '' ) {
				$row['eu_touched'] = wfTimestamp( TS_MW, $touched );
			}

			if ( is_int( $key ) ) {
				$key = $usage->getEntityId()->getSerialization() . '#' . $usage->getAspectKey();
			}

			$rows[$key] = $row;
		}

		return $rows;
	}

	/**
	 * @param array[] $rows
	 * @param EntityId[] $entityIds
	 *
	 * @return array[]
	 */
	private function removeRowsForEntities( array $rows, array $entityIds ) {
		$idStrings = array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $entityIds );

		foreach ( $rows as $key => $row ) {
			if ( in_array( $row['eu_entity_id'], $idStrings ) ) {
				unset( $rows[$key] );
			}
		}

		return $rows;
	}

	private function getEntityUsageTable( $batchSize = 1000 ) {
		return new EntityUsageTable(
			new BasicEntityIdParser(), wfGetDB( DB_MASTER ), SqlUsageTracker::DEFAULT_TABLE_NAME, $batchSize
		);
	}

	public function testAddUsages() {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		);

		$usagesT2 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$usageTable = $this->getEntityUsageTable();

		// adding usages should put them into the database
		$usageTable->addUsages( 23, $usagesT1, $t1 );

		$rowsT1 = $this->getUsageRows( 23, $usagesT1, $t1 );
		$this->assertUsageTableContains( $rowsT1 );

		// adding usages that were already tracked should be ignored
		$usageTable->addUsages( 23, $usagesT2, $t2 );

		$newRows = $this->getUsageRows( 23, array_diff( $usagesT2, $usagesT1 ), $t2 );
		$this->assertUsageTableContains( $newRows );

		$oldRows = $this->getUsageRows( 23, array_diff( $usagesT1, $usagesT2 ), $t1 );
		$this->assertUsageTableContains( $oldRows );

		// rows in T1 and T2 should still have timestamp T1
		$keepRows = $this->getUsageRows( 23, array_intersect( $usagesT1, $usagesT2 ), $t1 );
		$this->assertUsageTableContains( $keepRows );
	}

	public function testTouchUsage() {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		);

		$usagesT2 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$usageTable = $this->getEntityUsageTable();
		$usageTable->addUsages( 23, $usagesT1, $t1 );

		// touch usage entries (some non-existing)
		$usageTable->touchUsages( 23, $usagesT2, $t2 );

		// rows in T1 and T2 should now have timestamp T2
		$keepRows = $this->getUsageRows( 23, array_intersect( $usagesT1, $usagesT2 ), $t2 );
		$this->assertUsageTableContains( $keepRows );

		$extraRows = $this->getUsageRows( 23, array_diff( $usagesT2, $usagesT1 ), $t2 );
		$this->assertUsageTableDoesNotContain( $extraRows );

		$oldRows = $this->getUsageRows( 23, array_diff( $usagesT1, $usagesT2 ), $t1 );
		$this->assertUsageTableContains( $oldRows );
	}

	public function provideQueryUsages() {
		$t0 = '00000000000000';
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		);

		$usagesT2 = array(
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$usagesT1T2 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		return array(
			array( '>=', $t0, $usagesT1T2 ),
			array( '<', $t0, array() ),

			array( '<', $t1, array() ),
			array( '>=', $t1, $usagesT1T2 ),

			array( '<', $t2, $usagesT1 ),
			array( '>=', $t2, $usagesT2 ),

			array( '<', '"evil"', array() ),
			array( '<', '[evil]', $usagesT1T2 ),
		);
	}

	private function getUsageStrings( array $usages ) {
		$strings = array_map( function ( EntityUsage $usage ) {
			return $usage->getIdentityString();
		}, array_values( $usages ) );

		sort( $strings );
		return $strings;
	}

	/**
	 * @dataProvider provideQueryUsages
	 */
	public function testQueryUsages( $timeOp, $timestamp, $expected ) {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		);

		$usagesT2 = array(
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$usageTable = $this->getEntityUsageTable();

		$usageTable->addUsages( 23, $usagesT1, $t1 );
		$usageTable->addUsages( 23, $usagesT2, $t2 );

		// Add for different page, with swapped timestamps, to detect leakage between page ids.
		$usageTable->addUsages( 25, $usagesT1, $t2 );
		$usageTable->addUsages( 25, $usagesT2, $t1 );

		$usages = $usageTable->queryUsages( 23, $timeOp, $timestamp );

		$this->assertEquals(
			$this->getUsageStrings( $expected ),
			$this->getUsageStrings( $usages )
		);
	}

	public function provideQueryUsages_InvalidArgumentException() {
		return array(
			'$pageId is null' => array( null, '=', '00000000000000' ),
			'$pageId is false' => array( false, '=', '00000000000000' ),
			'$pageId is a string' => array( '-7', '=', '00000000000000' ),

			'$timeOp is empty' => array( 7, '', '00000000000000' ),
			'$timeOp is an int' => array( 7, 3, '00000000000000' ),
			'$timeOp is evil' => array( 7, 'not null --', '00000000000000' ),

			'$timestamp is empty' => array( 7,'>', '' ),
			'$timestamp is an int' => array( 7, '>', 3 ),

			'no $timeOp but $timestamp' => array( 7, null, '00000000000000' ),
			'$timeOp but no $timestamp' => array( 7, '>=', null ),
		);
	}

	/**
	 * @dataProvider provideQueryUsages_InvalidArgumentException
	 */
	public function testQueryUsages_InvalidArgumentException( $pageId, $timeOp, $timestamp ) {
		$usageTable = $this->getEntityUsageTable();

		$this->setExpectedException( 'InvalidArgumentException' );
		$usageTable->queryUsages( $pageId, $timeOp, $timestamp );
	}

	public function testPruneStaleUsages() {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		);

		$usagesT2 = array(
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$usageTable = $this->getEntityUsageTable();

		// init database: $usagesT2 get timestamp $t2,
		// array_diff( $usagesT1, $usagesT2 ) get timestamp $t1.
		$usageTable->addUsages( 23, $usagesT1, $t1 );
		$usageTable->addUsages( 23, $usagesT2, $t2 );

		// pruning should remove stale entries with a timestamp < $t2
		$stale = array_diff( $usagesT1, $usagesT2 );
		$pruned = $usageTable->pruneStaleUsages( 23, $t2 );

		$this->assertEquals(
			$this->getUsageStrings( $stale ),
			$this->getUsageStrings( $pruned ),
			'pruned'
		);

		$rowsT1_stale = $this->getUsageRows( 23, $stale, $t1 );
		$rowsT2 = $this->getUsageRows( 23, $usagesT2, $t2 );

		$this->assertUsageTableDoesNotContain( $rowsT1_stale );
		$this->assertUsageTableContains( $rowsT2 );
	}

	public function testRemoveEntities() {
		$touched = wfTimestamp( TS_MW );

		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usages = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$usageTable = $this->getEntityUsageTable();
		$usageTable->addUsages( 23, $usages, $touched );

		$rows = $this->getUsageRows( 23, $usages, $touched );
		$itemsToRemove = array( $q4, $q5 );

		$retainedRows = array_intersect_key( $rows, array( 'Q3#S' => 1, 'Q3#L' => 1 ) );
		$removedRows = array_intersect_key( $rows, array( 'Q4#L' => 1, 'Q5#X' => 1 ) );

		$usageTable->removeEntities( $itemsToRemove );

		$this->assertUsageTableContains( $retainedRows );
		$this->assertUsageTableDoesNotContain( $removedRows );
	}

	public function testAddTouchUsages_batching() {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$usages = $this->makeUsages( 10 );
		$rowsT1 = $this->getUsageRows( 7, $usages, $t1 );
		$rowsT2 = $this->getUsageRows( 7, $usages, $t2 );

		$usageTable = $this->getEntityUsageTable( 3 );

		// inserting more rows than fit into a single batch
		$usageTable->addUsages( 7, $usages, $t1 );
		$this->assertUsageTableContains( $rowsT1 );

		// touching more rows than fit into a single batch
		$usageTable->touchUsages( 7, $usages, $t2 );
		$this->assertUsageTableContains( $rowsT2 );
	}

	public function testRemoveEntities_batching() {
		$touched = wfTimestamp( TS_MW );
		$usages = $this->makeUsages( 10 );
		$rows7 = $this->getUsageRows( 7, $usages, $touched );
		$rows8 = $this->getUsageRows( 8, $usages, $touched );

		$usageTable = $this->getEntityUsageTable( 3 );
		$usageTable->addUsages( 7, $usages, $touched );
		$usageTable->addUsages( 8, $usages, $touched );

		// removing more rows than fit into a single batch
		$entitiesToRemove = array_slice( $this->getItemIds( $usages ), 0, 5 );
		$usageTable->removeEntities( $entitiesToRemove );

		$this->assertUsageTableContains( $this->removeRowsForEntities( $rows7, $entitiesToRemove ) );
		$this->assertUsageTableContains( $this->removeRowsForEntities( $rows8, $entitiesToRemove ) );
	}

	public function testGetPagesUsing() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3s = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE );
		$u4t = new EntityUsage( $q4, EntityUsage::TITLE_USAGE );

		$usageTable = $this->getEntityUsageTable( 3 );
		$usageTable->addUsages( 23, array( $u3s, $u3l, $u4l ), '20150102030405' );
		$usageTable->addUsages( 42, array( $u4l, $u4t ), '20150102030405' );

		$this->assertEmpty(
			iterator_to_array( $usageTable->getPagesUsing( array( $q6 ) ) )
		);

		$this->assertSamePageEntityUsages(
			array( 23 => new PageEntityUsages( 23, array( $u3s, $u3l ) ) ),
			iterator_to_array( $usageTable->getPagesUsing( array( $q3 ) ) ),
			'Pages using Q3'
		);

		$this->assertSamePageEntityUsages(
			array(
				23 => new PageEntityUsages( 23, array( $u3l, $u4l ) ),
				42 => new PageEntityUsages( 42, array( $u4l ) ),
			),
			iterator_to_array( $usageTable->getPagesUsing( array( $q4, $q3 ), array( EntityUsage::LABEL_USAGE ) ) ),
			'Pages using "label" on Q4 or Q3'
		);

		$this->assertEmpty(
			iterator_to_array( $usageTable->getPagesUsing( array( $q3 ), array( EntityUsage::ALL_USAGE ) ) ),
			'Pages using "all" on Q3'
		);

		$this->assertEmpty(
			iterator_to_array( $usageTable->getPagesUsing( array( $q4 ), array( EntityUsage::SITELINK_USAGE ) ) ),
			'Pages using "sitelinks" on Q4'
		);

		$this->assertCount( 2,
			iterator_to_array( $usageTable->getPagesUsing( array( $q3, $q4 ), array( EntityUsage::TITLE_USAGE, EntityUsage::SITELINK_USAGE ) ) ),
			'Pages using "title" or "sitelinks" on Q3 or Q4'
		);

		$usageTable->addUsages( 23, array(), '20150102030405' );
	}

	/**
	 *
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

			$this->assertEquals( $expectedUsages->getPageId(), $actualUsages->getPageId(), $message . "[Page $key] " . 'Page ID mismatches!' );
			$this->assertEquals( $expectedUsages->getUsages(), $actualUsages->getUsages(), $message . "[Page $key] " . 'Usages:' );
		}

		$this->assertEmpty( array_slice( $actual, count( $expected ) ), $message . 'Extra entries found!' );
	}

	public function testGetUnusedEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3i = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE );

		$usages = array( $u3i, $u3l, $u4l );

		$usageTable = $this->getEntityUsageTable( 3 );
		$usageTable->addUsages( 23, $usages, '20150102030405' );

		$this->assertEmpty( $usageTable->getUnusedEntities( array( $q4 ) ), 'Q4 should not be unused' );

		$unused = $usageTable->getUnusedEntities( array( $q4, $q6 ) );
		$this->assertCount( 1, $unused );
		$this->assertEquals( $q6, reset( $unused ), 'Q6 shouold be unused' );
	}

	/**
	 * @param array[] $rows
	 */
	private function assertUsageTableContains( array $rows ) {
		$db = wfGetDB( DB_SLAVE );

		foreach ( $rows as $row ) {
			$name = preg_replace( '/\s+/s', ' ', print_r( $row, true ) );
			$this->assertTrue( $this->rowExists( $db, $row ), "Missing row: $name" );
		}
	}

	/**
	 * @param array[] $rows
	 */
	private function assertUsageTableDoesNotContain( array $rows ) {
		$db = wfGetDB( DB_SLAVE );

		foreach ( $rows as $row ) {
			$name = preg_replace( '/\s+/s', ' ', print_r( $row, true ) );
			$this->assertFalse( $this->rowExists( $db, $row ), "Unexpected row: $name" );
		}
	}

	/**
	 * @param DatabaseBase $db
	 * @param mixed $conditions
	 *
	 * @return bool
	 */
	private function rowExists( DatabaseBase $db, $conditions ) {
		$count = $db->selectRowCount( SqlUsageTracker::DEFAULT_TABLE_NAME, '*', $conditions );
		return $count > 0;
	}

}
