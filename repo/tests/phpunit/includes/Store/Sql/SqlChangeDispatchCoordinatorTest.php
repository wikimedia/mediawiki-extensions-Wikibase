<?php

namespace Wikibase\Test;

use Wikibase\Store\Sql\SqlChangeDispatchCoordinator;

/**
 * @covers Wikibase\Store\Sql\SqlChangeDispatchCoordinator
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group WikibaseRepo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlChangeDispatchCoordinatorTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'wb_changes';
		$this->tablesUsed[] = 'wb_changes_dispatch';
	}

	private function getCoordinator() {
		$coordinator = new SqlChangeDispatchCoordinator( false, 'TestRepo' );

		$coordinator->setBatchSize( 3 );
		$coordinator->setRandomness( 3 );
		$coordinator->setLockGraceInterval( 120 );
		$coordinator->setDispatchInterval( 60 );

		$coordinator->setArrayRandOverride( function( $array ) {
			$keys = array_keys( $array );
			$last = end( $keys );
			return $last;
		} );

		$coordinator->setTimeOverride( function() {
			return wfTimestamp( TS_UNIX, '20140303000000' );
		} );

		$coordinator->setIsClientLockUsedOverride( function( $db, $lockName ) {
			return $lockName === 'Wikibase.TestRepo.dispatchChanges.zhwiki';
		} );

		$coordinator->setEngageClientLockOverride( function( $db, $lockName ) {
			return $lockName !== 'Wikibase.TestRepo.dispatchChanges.zhwiki';
		} );

		$coordinator->setReleaseClientLockOverride( function( $db, $lockName ) {
			return true;
		} );

		return $coordinator;
	}

	private function resetChangesTable( $id = 23 ) {
		$dbw = wfGetDB( DB_MASTER );

		$row = array(
			'change_id' => $id,
			'change_type' => 'test',
			'change_time' => '20140303000000',
			'change_object_id' => '678',
			'change_revision_id' => '6789',
			'change_user_id' => '12345',
			'change_info' => '',
		);

		$dbw->delete( 'wb_changes', '*', __METHOD__ );
		$dbw->insert( 'wb_changes', $row, __METHOD__ );
	}

	private function insertChangesDispatchRows( array $rows ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert( 'wb_changes_dispatch', array_values( $rows ), __METHOD__ );
	}

	private function fetchChangesDispatchRows( $where = '' ) {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select( 'wb_changes_dispatch', '*', $where, __METHOD__, array( 'ORDER BY' => 'chd_site' ) );

		$rows = [];
		foreach ( $res as $row ) {
			$rows[] = get_object_vars( $row );
		}

		return $rows;
	}

	public function testInitState() {
		$coordinator = $this->getCoordinator();

		$clientWikis = array(
			'dewiki' => 'dewikidb',
			'enwiki' => 'enwikidb',
			'nlwiki' => 'nlwikidb',
			'ruwiki' => 'ruwikidb',
			'zhwiki' => 'zhwikidb',
		);

		$coordinator->initState( $clientWikis );

		$rows = $this->fetchChangesDispatchRows();

		$this->assertEquals( array(
			array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			array(
				'chd_site' => 'nlwiki',
				'chd_db' => 'nlwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			array(
				'chd_site' => 'ruwiki',
				'chd_db' => 'ruwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			array(
				'chd_site' => 'zhwiki',
				'chd_db' => 'zhwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
		), $rows );
	}

	public function testLockClient() {
		$this->insertChangesDispatchRows( array(
			array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
		) );

		$coordinator = $this->getCoordinator();

		$coordinator->lockClient( 'dewiki' );

		$rows = $this->fetchChangesDispatchRows();

		$this->assertEquals( array(
			array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '0',
				'chd_touched' => '20140303000000',
				'chd_lock' => "Wikibase.TestRepo.dispatchChanges.dewiki",
				'chd_disabled' => '0',
			),
			array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
		), $rows );
	}

	public function testReleaseClient() {
		$this->insertChangesDispatchRows( array(
			array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '0',
				'chd_touched' => '20140101000055',
				'chd_lock' => "Wikibase.TestRepo.dispatchChanges.dewiki",
				'chd_disabled' => '0',
			),
			array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
		) );

		$coordinator = $this->getCoordinator();

		$state = array(
			'chd_site' => 'dewiki',
			'chd_db' => 'dewikidb',
			'chd_seen' => 23,
			'chd_touched' => '20140101000055',
			'chd_lock' => "Wikibase.TestRepo.dispatchChanges.dewiki",
			'chd_disabled' => '0',
		);

		$coordinator->releaseClient( $state );

		$rows = $this->fetchChangesDispatchRows();

		$this->assertEquals( array(
			array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '23',
				'chd_touched' => '20140303000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
		), $rows );
	}

	public function provideSelectClient() {
		// NOTE: Our fake random function always returns the last element
		//       and randomness is set to 3, so the third (or last) eligible
		//       wiki will be selected.
		// NOTE: The id of the last change is 23, and its timestamp is 20140303000000.
		//       The batch size is 5, lock grace is 120, and dispatch interval is 60.

		$vanillaRows = array(
			'dewiki' => array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '11',
				'chd_touched' => '20140301110000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			'enwiki' => array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '5',
				'chd_touched' => '20140301050000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			'ruwiki' => array(
				'chd_site' => 'ruwiki',
				'chd_db' => 'ruwikidb',
				'chd_seen' => '4',
				'chd_touched' => '20140301040000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			'nlwiki' => array(
				'chd_site' => 'nlwiki',
				'chd_db' => 'nlwikidb',
				'chd_seen' => '7',
				'chd_touched' => '20140301070000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
		);

		$lockedRows = array(
			'dewiki' => array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '0',
				'chd_touched' => '20140302235955',
				'chd_lock' => 'Foo.Bar', // locked
				'chd_disabled' => 0,
			),
			'enwiki' => array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => 1, // disabled
			),
			'zhwiki' => array(
				'chd_site' => 'zhwiki',
				'chd_db' => 'zhwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
		);

		$recentRows = array(
			'dewiki' => array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '0', // massive lag!
				'chd_touched' => '20140302235907', // < dispatch interval
				'chd_lock' => null,
				'chd_disabled' => '0',
			),
			'enwiki' => array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '21', // diff < batch size
				'chd_touched' => '20140302235907', // < dispatch interval
				'chd_lock' => null,
				'chd_disabled' => '0',
			)
		);

		$brokenLockRows = array(
			'dewiki' => array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '0',
				'chd_touched' => '20140302235955',
				'chd_lock' => 'Foo.Bar', // locked, but not really
				'chd_disabled' => 0,
			),
			'enwiki' => array(
				'chd_site' => 'enwiki',
				'chd_db' => 'enwikidb',
				'chd_seen' => '0',
				'chd_touched' => '00000000000000', // oooold timestamp
				'chd_lock' => 'Foo.Bar', // locked
				'chd_disabled' => 0,
			)
		);

		$noPendingRows = array(
			'dewiki' => array(
				'chd_site' => 'dewiki',
				'chd_db' => 'dewikidb',
				'chd_seen' => '23', // nothing to do!
				'chd_touched' => '00000000000000',
				'chd_lock' => null,
				'chd_disabled' => 0,
			),
		);

		return array(
			'most lagged first' => array(
				$vanillaRows,
				array( // this is actually the *third* most lagged, because of our fake random routine.
					'chd_site' => 'nlwiki',
					'chd_db' => 'nlwikidb',
					'chd_seen' => '7',
					'chd_touched' => '20140303000000',
					'chd_lock' => 'Wikibase.TestRepo.dispatchChanges.nlwiki',
				)
			),
			'locked or disabled' => array(
				$lockedRows,
				null
			),
			'skip recently processed unless lagged' => array(
				$recentRows,
				array(
					'chd_site' => 'dewiki',
					'chd_db' => 'dewikidb',
					'chd_seen' => '0',
					'chd_touched' => '20140303000000',
					'chd_lock' => 'Wikibase.TestRepo.dispatchChanges.dewiki',
				)
			),
			'broken lock' => array(
				$brokenLockRows,
				array(
					'chd_site' => 'enwiki',
					'chd_db' => 'enwikidb',
					'chd_seen' => '0',
					'chd_touched' => '20140303000000',
					'chd_lock' => 'Wikibase.TestRepo.dispatchChanges.enwiki',
				)
			),
			'no pending changed' => array(
				$noPendingRows,
				null
			),
		);
	}

	/**
	 * @dataProvider provideSelectClient
	 */
	public function testSelectClient( array $chdRows, $expected ) {
		$this->resetChangesTable();
		$this->insertChangesDispatchRows( $chdRows );

		$coordinator = $this->getCoordinator();

		$selected = $coordinator->selectClient();
		$this->assertEquals( $expected, $selected );

		if ( $expected !== null ) {
			// Also check that the database was updated to reflect the selection and locking of the client wiki.
			$rows = $this->fetchChangesDispatchRows( array( 'chd_site' => $expected['chd_site'] ) );

			$actualRow = array_shift( $rows );
			unset( $actualRow['chd_disabled'] );

			$this->assertEquals( $expected, $actualRow );
		}
	}

}
