<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use stdClass;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\Store\Sql\DispatchStats;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\DBConnRef;

/**
 * @covers \Wikibase\Repo\Store\Sql\DispatchStats
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DispatchStatsTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	/**
	 * Creates and loads a DispatchStats object, injecting test data into
	 * the database as needed.
	 *
	 * @return \Wikibase\Repo\Store\Sql\DispatchStats
	 */
	private function getDispatchStats() {
		$data = $this->getTestData();
		$now = $data['now'];
		$changes = $data['changes'];
		$states = $data['states'];

		$this->db->delete( 'wb_changes', [ "1=1" ] );
		$this->db->delete( 'wb_changes_dispatch', [ "1=1" ] );

		foreach ( $changes as $row ) {
			if ( $row === null ) {
				continue;
			}

			if ( !isset( $row['change_revision_id'] ) ) {
				$row['change_revision_id'] = 0;
			}

			if ( !isset( $row['change_user_id'] ) ) {
				$row['change_user_id'] = 0;
			}

			if ( !isset( $row['change_info'] ) ) {
				$row['change_info'] = ''; // ugh
			}

			$this->db->insert( 'wb_changes',
				$row,
				__METHOD__
			);
		}

		foreach ( $states as $row ) {
			if ( $row === null ) {
				continue;
			}

			$this->db->insert( 'wb_changes_dispatch',
				$row,
				__METHOD__
			);
		}

		$stats = new DispatchStats( $this->getRepoDomainDb( $this->db ) );
		$stats->load( $now );
		return $stats;
	}

	private function getTestData() {
		return [
			'states' => [
				[
					'chd_site' => 'xywiki',
					'chd_db' => 'xywiki',
					'chd_seen' => 1,
					'chd_touched' => '20130303000110',
					'chd_lock' => null,
					'chd_disabled' => 1,
				],
				[
					'chd_site' => 'enwiki',
					'chd_db' => 'enwiki',
					'chd_seen' => 3,
					'chd_touched' => '20130303000330',
					'chd_lock' => null,
					'chd_disabled' => 0,
				],
				[
					'chd_site' => 'dewiki',
					'chd_db' => 'dewiki',
					'chd_seen' => 2,
					'chd_touched' => '20130303000220',
					'chd_lock' => 'LOCK',
					'chd_disabled' => 0,
				],
				[
					'chd_site' => 'frwiki',
					'chd_db' => 'frwiki',
					'chd_seen' => 1,
					'chd_touched' => '20130303000110',
					'chd_lock' => null,
					'chd_disabled' => 0,
				],
			],
			'changes' => [
				2 => [
					'change_id' => 2,
					'change_time' => '20130303000200',
					'change_type' => 'test',
					'change_object_id' => 'test',
				],
				3 => [
					'change_id' => 3,
					'change_time' => '20130303000300',
					'change_type' => 'test',
					'change_object_id' => 'test',
				],
				1 => [
					'change_id' => 1,
					'change_time' => '20130303000100',
					'change_type' => 'test',
					'change_object_id' => 'test',
				],
			],

			'now' => '20130303000400',

			'expected' => [
				'getClientStates' => [
					[
						'chd_site' => 'frwiki',
						'chd_seen' => 1,
						'chd_touched' => '20130303000110',
						'chd_untouched' => 170,
						'chd_pending' => 2,
						'chd_lag' => 170,
					],
					[
						'chd_site' => 'dewiki',
						'chd_seen' => 2,
						'chd_touched' => '20130303000220',
						'chd_untouched' => 100,
						'chd_pending' => 1,
						'chd_lag' => 100,
					],
					[
						'chd_site' => 'enwiki',
						'chd_seen' => 3,
						'chd_touched' => '20130303000330',
						'chd_untouched' => 30,
						'chd_pending' => 0,
						'chd_lag' => 30,
					],
				],

				'getClientCount' => 3,
				'getLockedCount' => 1,

				'getMaxChangeId' => 3,
				'getMinChangeId' => 1,
				'getMaxChangeTimestamp' => '20130303000300',
				'getMinChangeTimestamp' => '20130303000100',

				'getFreshest' => [
					'chd_site' => 'enwiki',
					'chd_seen' => 3,
					'chd_touched' => '20130303000330',
					'chd_untouched' => 30,
					'chd_pending' => 0,
					'chd_lag' => 30,
				],
				'getStalest' => [
					'chd_site' => 'frwiki',
					'chd_seen' => 1,
					'chd_touched' => '20130303000110',
					'chd_untouched' => 170,
					'chd_pending' => 2,
					'chd_lag' => 170,
				],
				'getMedian' => [
					'chd_site' => 'frwiki',
					'chd_seen' => 1,
					'chd_touched' => '20130303000110',
					'chd_untouched' => 100,
					'chd_pending' => 1,
					'chd_lag' => 170,
				],
				'getAverage' => [
					'chd_untouched' => 100,
					'chd_pending' => 1,
					'chd_lag' => 170,
				],
			],
		];
	}

	public function provideGetClientStates() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getClientStates'],
			]
		];
	}

	/**
	 * @dataProvider provideGetClientStates
	 * @param array[] $expected
	 */
	public function testGetClientStates( array $expected ) {
		$stats = $this->getDispatchStats();

		$states = $stats->getClientStates();

		$this->assertSame( count( $expected ), count( $states ), "number of state objects" );

		reset( $expected );
		reset( $states );
		foreach ( $states as $state ) {
			$this->assertStateEquals( current( $expected ), $state );
			next( $expected );
		}
	}

	/**
	 * @param array $expected
	 * @param stdClass $actual
	 */
	private function assertStateEquals( array $expected, stdClass $actual ) {
		$suffix = '';

		if ( isset( $expected['chd_site'] ) ) {
			$this->assertEquals( $expected['chd_site'], $actual->chd_site, 'site' );
			$suffix .= '/' . $expected['chd_site'];
		}

		if ( isset( $expected['chd_seen'] ) ) {
			$this->assertEquals( $expected['chd_seen'], $actual->chd_seen, "seen$suffix" );
		}

		if ( isset( $expected['chd_touched'] ) ) {
			$this->assertEquals( $expected['chd_touched'], $actual->chd_touched, "touched$suffix" );
		}

		if ( isset( $expected['chd_lag'] ) ) {
			$this->assertEquals( $expected['chd_lag'], $actual->chd_untouched, "lag$suffix" );
		}

		if ( isset( $expected['chd_dist'] ) ) {
			$this->assertEquals( $expected['chd_dist'], $actual->chd_pending, "dist$suffix" );
		}
	}

	public function provideGetClientCount() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getClientCount'],
			]
		];
	}

	/**
	 * @dataProvider provideGetClientCount
	 * @param int $expected
	 */
	public function testGetClientCount( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getClientCount() );
	}

	public function provideGetLockedCount() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getLockedCount'],
			]
		];
	}

	/**
	 * @dataProvider provideGetLockedCount
	 * @param int $expected
	 */
	public function testGetLockedCount( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getLockedCount() );
	}

	public function provideGetMinChangeId() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getMinChangeId'],
			]
		];
	}

	/**
	 * @dataProvider provideGetMinChangeId
	 * @param int $expected
	 */
	public function testGetMinChangeId( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getMinChangeId() );
	}

	public function provideGetMaxChangeId() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getMaxChangeId'],
			]
		];
	}

	/**
	 * @dataProvider provideGetMaxChangeId
	 * @param int $expected
	 */
	public function testGetMaxChangeId( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getMaxChangeId() );
	}

	public function provideGetMinChangeTimestamp() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getMinChangeTimestamp'],
			]
		];
	}

	/**
	 * @dataProvider provideGetMinChangeTimestamp
	 * @param string $expected
	 */
	public function testGetMinChangeTimestamp( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getMinChangeTimestamp() );
	}

	public function provideGetMaxChangeTimestamp() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getMaxChangeTimestamp'],
			]
		];
	}

	/**
	 * @dataProvider provideGetMaxChangeTimestamp
	 * @param string $expected
	 */
	public function testGetMaxChangeTimestamp( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getMaxChangeTimestamp() );
	}

	public function provideGetFreshest() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getFreshest'],
			]
		];
	}

	/**
	 * @dataProvider provideGetFreshest
	 */
	public function testGetFreshest( array $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertStateEquals( $expected, $stats->getFreshest() );
	}

	public function provideGetStalest() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getStalest'],
			]
		];
	}

	/**
	 * @dataProvider provideGetStalest
	 */
	public function testGetStalest( array $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertStateEquals( $expected, $stats->getStalest() );
	}

	public function provideGetAverage() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getAverage'],
			]
		];
	}

	/**
	 * @dataProvider provideGetAverage
	 */
	public function testGetAverage( array $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertStateEquals( $expected, $stats->getStalest() );
	}

	public function provideGetMedian() {
		$data = $this->getTestData();

		return [
			[
				$data['expected']['getMedian'],
			]
		];
	}

	/**
	 * @dataProvider provideGetMedian
	 */
	public function testGetMedian( array $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertStateEquals( $expected, $stats->getStalest() );
	}

	public function testHasStats() {
		$stats = $this->getDispatchStats();

		$this->assertTrue( $stats->hasStats() );

		// No stats there before load has been called.
		$unloadedStats = new DispatchStats( $this->getRepoDomainDb( $this->db ) );
		$this->assertFalse( $unloadedStats->hasStats() );
	}

	public function testHasNoStats() {
		$this->db->delete( 'wb_changes', '*' );
		$this->db->delete( 'wb_changes_dispatch', '*' );

		$stats = new DispatchStats( $this->getRepoDomainDb( $this->db ) );
		$stats->load( time() );

		$this->assertFalse( $stats->hasStats() ); // Still no stats as the table is empty
	}

	private function getRepoDomainDbMock( DBConnRef $connRef ): RepoDomainDb {
		$connManagerMock = $this->createMock( ConnectionManager::class );
		$connManagerMock->method( 'getReadConnectionRef' )->willReturn( $connRef );
		$dbMock = $this->createMock( RepoDomainDb::class );
		$dbMock->method( 'connections' )->willReturn( $connManagerMock );

		return $dbMock;
	}

	public function testGetDispatchStats_empty(): void {
		$connectionRefMock = $this->createMock( DBConnRef::class );
		$connectionRefMock->method( 'selectRowCount' )->willReturn( 0 );
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $connectionRefMock ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( $actualStats, [ 'numberOfChanges' => 0 ] );
	}

	public function testGetDispatchStats_exact(): void {
		$connectionRefMock = $this->createMock( DBConnRef::class );
		$connectionRefMock->method( 'selectRowCount' )->willReturn( 3 );
		$connectionRefMock->method( 'selectRow' )->willReturn(
			(object)[
				'freshestTime' => '20211018155646',
				'stalestTime' => '20211018155100',
			],
			(object)[
				'numberOfEntities' => '2',
			]
		);
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $connectionRefMock ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( $actualStats, [
			'numberOfChanges' => 3,
			'numberOfEntities' => 2,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
	}

	public function testGetDispatchStats_estimated(): void {
		$connectionRefMock = $this->createMock( DBConnRef::class );
		$connectionRefMock->method( 'selectRowCount' )->willReturn( 5001 );
		$connectionRefMock->method( 'estimateRowCount' )->willReturn( 30000 );
		$connectionRefMock->method( 'selectRow' )->willReturn(
			(object)[
				'freshestTime' => '20211018155646',
				'stalestTime' => '20211018155100',
			]
		);
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $connectionRefMock ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( $actualStats, [
			'estimatedNumberOfChanges' => 30000,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
	}

	public function testGetDispatchStats_estimateOutdated(): void {
		$connectionRefMock = $this->createMock( DBConnRef::class );
		$connectionRefMock->method( 'selectRowCount' )->willReturn( 5001 );
		$connectionRefMock->method( 'selectRow' )->willReturn(
			(object)[
				'freshestTime' => '20211018155646',
				'stalestTime' => '20211018155100',
			]
		);
		$connectionRefMock->method( 'estimateRowCount' )->willReturn( 400 );
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $connectionRefMock ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( $actualStats, [
			'minimumNumberOfChanges' => 5001,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
	}

}
