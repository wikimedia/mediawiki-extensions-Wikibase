<?php

namespace Wikibase\Client\Tests\Usage\Sql;

use DatabaseBase;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\Sql\UsageTableUpdater;
use Wikibase\Client\WikibaseClient;
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

			if ( $pageId > 0 ) {
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
		return new UsageTableUpdater( wfGetDB( DB_MASTER ), 'wbc_entity_usage', $batchSize );
	}

	public function testUpdateUsage() {
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
		$tableUpdater->updateUsage( 23, array(), $usagesT1, $t1 );

		$rowsT1 = $this->getUsageRows( 23, $usagesT1, $t1 );
		$this->assertUsageTableContains( $rowsT1 );

		// adding usages that were already tracked should updated the timestamp
		$tableUpdater->updateUsage( 23, $usagesT1, $usagesT2, $t2 );

		$rowsT2 = $this->getUsageRows( 23, $usagesT2, $t2 );
		$rowsT1_stale = $this->getUsageRows( 23, array_diff( $usagesT1, $usagesT2 ), $t1 );

		$this->assertUsageTableContains( $rowsT1_stale );
		$this->assertUsageTableContains( $rowsT2 );

		// adding nothing should change nothing
		$tableUpdater->updateUsage( 23, $usagesT2, array(), $t2 );

		$this->assertUsageTableContains( $rowsT1_stale );
		$this->assertUsageTableContains( $rowsT2 );
	}

	public function testPruneStaleUsages() {
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

		// init database: $usagesT2 get timestamp $t2,
		// array_diff( $usagesT1, $usagesT2 ) get timestamp $t1.
		$tableUpdater->updateUsage( 23, array(), $usagesT1, $t1 );
		$tableUpdater->updateUsage( 23, $usagesT1, $usagesT2, $t2 );

		// pruning should remove entries with a timestamp < $t2
		$tableUpdater->pruneStaleUsages( 23, $t2 );

		$rowsT1_stale = $this->getUsageRows( 23, array_diff( $usagesT1, $usagesT2 ), $t1 );
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
		$tableUpdater->updateUsage( 23, array(), $usages, $touched );

		$rows = $this->getUsageRows( 23, $usages, $touched );
		$itemsToRemove = array( $q4, $q5 );

		$retainedRows = array_intersect_key( $rows, array( 'Q3#S' => 1, 'Q3#L' => 1 ) );
		$removedRows = array_intersect_key( $rows, array( 'Q4#L' => 1, 'Q5#X' => 1 ) );

		$tableUpdater->removeEntities( $itemsToRemove );

		$this->assertUsageTableContains( $retainedRows );
		$this->assertUsageTableDoesNotContain( $removedRows );
	}

	public function testTrackUsedEntities_batching() {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$usages = $this->makeUsages( 10 );
		$rowsT1 = $this->getUsageRows( 7, $usages, $t1 );
		$rowsT2 = $this->getUsageRows( 7, $usages, $t2 );

		$tableUpdater = $this->getUsageTableUpdater( 3 );

		// inserting more rows than fit into a single batch
		$tableUpdater->updateUsage( 7, array(), $usages, $t1 );
		$this->assertUsageTableContains( $rowsT1 );

		// touching more rows than fit into a single batch
		$tableUpdater->updateUsage( 7, $usages, $usages, $t2 );
		$this->assertUsageTableContains( $rowsT2 );
	}

	public function testRemoveEntities_batching() {
		$touched = wfTimestamp( TS_MW );
		$usages = $this->makeUsages( 10 );
		$rows7 = $this->getUsageRows( 7, $usages, $touched );
		$rows8 = $this->getUsageRows( 8, $usages, $touched );

		$tableUpdater = $this->getUsageTableUpdater( 3 );
		$tableUpdater->updateUsage( 7, array(), $usages, $touched );
		$tableUpdater->updateUsage( 8, array(), $usages, $touched );

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
