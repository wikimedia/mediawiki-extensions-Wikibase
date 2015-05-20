<?php

namespace Wikibase\Client\Tests\Usage\Sql;

use DatabaseBase;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\Sql\UsageTableUpdater;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Usage\Sql\UsageTableUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageTableUpdaterTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		if ( WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'useLegacyUsageIndex' ) ) {
			$this->markTestSkipped( 'Skipping test for UsageTableUpdater, because the useLegacyUsageIndex option is set.' );
		}

		$this->tablesUsed[] = 'wbc_entity_usage';
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

	private function getUsageTableUpdater( $batchSize = 1000 ) {
		return new UsageTableUpdater(
			wfGetDB( DB_MASTER ),
			'wbc_entity_usage',
			$batchSize,
			new BasicEntityIdParser()
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

		$tableUpdater = $this->getUsageTableUpdater();

		// adding usages should put them into the database
		$tableUpdater->addUsages( 23, $usagesT1, $t1 );

		$rowsT1 = $this->getUsageRows( 23, $usagesT1, $t1 );
		$this->assertUsageTableContains( $rowsT1 );

		// adding usages that were already tracked should be ignored
		$tableUpdater->addUsages( 23, $usagesT2, $t2 );

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

		$tableUpdater = $this->getUsageTableUpdater();
		$tableUpdater->addUsages( 23, $usagesT1, $t1 );

		// touch usage entries (some non-existing)
		$tableUpdater->touchUsages( 23, $usagesT2, $t2 );

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
			array( '<=', $t1, $usagesT1 ),
			array( '=', $t1, $usagesT1 ),
			array( '>=', $t1, $usagesT1T2 ),
			array( '>', $t1, $usagesT2 ),

			array( '<', $t2, $usagesT1 ),
			array( '<=', $t2, $usagesT1T2 ),
			array( '=', $t2, $usagesT2 ),
			array( '>=', $t2, $usagesT2 ),
			array( '>', $t2, array() ),

			array( '>=', null, $usagesT1T2 ),
			array( null, $t2, $usagesT1T2 ),

			array( '<', '"evil"', array() ),
			array( '<', '[evil]', array() ),
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

		$tableUpdater = $this->getUsageTableUpdater();

		$tableUpdater->addUsages( 23, $usagesT1, $t1 );
		$tableUpdater->addUsages( 23, $usagesT2, $t2 );

		// Add for different page, with swapped timestamps, to detect leakage between page ids.
		$tableUpdater->addUsages( 25, $usagesT1, $t2 );
		$tableUpdater->addUsages( 25, $usagesT2, $t1 );

		$usages = $tableUpdater->queryUsages( 23, $timeOp, $timestamp );

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
		);
	}

	/**
	 * @dataProvider provideQueryUsages_InvalidArgumentException
	 */
	public function testQueryUsages_InvalidArgumentException( $pageId, $timeOp, $timestamp ) {
		$tableUpdater = $this->getUsageTableUpdater();

		$this->setExpectedException( 'InvalidArgumentException' );
		$tableUpdater->queryUsages( $pageId, $timeOp, $timestamp );
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

		$tableUpdater = $this->getUsageTableUpdater();

		// init database: $usagesT2 get timestamp $t2,
		// array_diff( $usagesT1, $usagesT2 ) get timestamp $t1.
		$tableUpdater->addUsages( 23, $usagesT1, $t1 );
		$tableUpdater->addUsages( 23, $usagesT2, $t2 );

		// pruning should remove stale entries with a timestamp < $t2
		$stale = array_diff( $usagesT1, $usagesT2 );
		$pruned = $tableUpdater->pruneStaleUsages( 23, $t2 );

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

		$tableUpdater = $this->getUsageTableUpdater();
		$tableUpdater->addUsages( 23, $usages, $touched );

		$rows = $this->getUsageRows( 23, $usages, $touched );
		$itemsToRemove = array( $q4, $q5 );

		$retainedRows = array_intersect_key( $rows, array( 'Q3#S' => 1, 'Q3#L' => 1 ) );
		$removedRows = array_intersect_key( $rows, array( 'Q4#L' => 1, 'Q5#X' => 1 ) );

		$tableUpdater->removeEntities( $itemsToRemove );

		$this->assertUsageTableContains( $retainedRows );
		$this->assertUsageTableDoesNotContain( $removedRows );
	}

	public function testAddTouchUsages_batching() {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$usages = $this->makeUsages( 10 );
		$rowsT1 = $this->getUsageRows( 7, $usages, $t1 );
		$rowsT2 = $this->getUsageRows( 7, $usages, $t2 );

		$tableUpdater = $this->getUsageTableUpdater( 3 );

		// inserting more rows than fit into a single batch
		$tableUpdater->addUsages( 7, $usages, $t1 );
		$this->assertUsageTableContains( $rowsT1 );

		// touching more rows than fit into a single batch
		$tableUpdater->touchUsages( 7, $usages, $t2 );
		$this->assertUsageTableContains( $rowsT2 );
	}

	public function testRemoveEntities_batching() {
		$touched = wfTimestamp( TS_MW );
		$usages = $this->makeUsages( 10 );
		$rows7 = $this->getUsageRows( 7, $usages, $touched );
		$rows8 = $this->getUsageRows( 8, $usages, $touched );

		$tableUpdater = $this->getUsageTableUpdater( 3 );
		$tableUpdater->addUsages( 7, $usages, $touched );
		$tableUpdater->addUsages( 8, $usages, $touched );

		// removing more rows than fit into a single batch
		$entitiesToRemove = array_slice( $this->getItemIds( $usages ), 0, 5 );
		$tableUpdater->removeEntities( $entitiesToRemove );

		$this->assertUsageTableContains( $this->removeRowsForEntities( $rows7, $entitiesToRemove ) );
		$this->assertUsageTableContains( $this->removeRowsForEntities( $rows8, $entitiesToRemove ) );
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
		$count = $db->selectRowCount( 'wbc_entity_usage', '*', $conditions );
		return $count > 0;
	}

}
