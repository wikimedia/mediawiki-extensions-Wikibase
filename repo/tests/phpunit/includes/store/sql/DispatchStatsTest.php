<?php

namespace Wikibase\Tests\Repo;

use Wikibase\DispatchStats;

/**
 * @covers Wikibase\DispatchStats
 *
 * @since 0.4
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchStatsTest extends \MediaWikiTestCase {

	/**
	 * Creates and loads a DispatchStats object, injecting test data into
	 * the database as needed.
	 *
	 * @return DispatchStats
	 */
	protected function getDispatchStats() {
		$data = self::getTestData();
		$now = $data['now'];
		$changes = $data['changes'];
		$states = $data['states'];

		$dbw = wfGetDB( DB_MASTER ); // writes to dummy tables

		$dbw->delete( 'wb_changes', array( "1" ) );
		$dbw->delete( 'wb_changes_dispatch', array( "1" ) );

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

			$dbw->insert( 'wb_changes',
				$row,
				__METHOD__
			);
		}

		foreach ( $states as $row ) {
			if ( $row === null ) {
				continue;
			}

			$dbw->insert( 'wb_changes_dispatch',
				$row,
				__METHOD__
			);
		}

		$stats = new DispatchStats();
		$stats->load( $now );
		return $stats;
	}

	protected static function getTestData() {
		return array(
			'states' => array(
				array(
					'chd_site' => 'xywiki',
					'chd_db' => 'xywiki',
					'chd_seen' => 1,
					'chd_touched' => '20130303000110',
					'chd_lock' => null,
					'chd_disabled' => 1,
				),
				array(
					'chd_site' => 'enwiki',
					'chd_db' => 'enwiki',
					'chd_seen' => 3,
					'chd_touched' => '20130303000330',
					'chd_lock' => null,
					'chd_disabled' => 0,
				),
				array(
					'chd_site' => 'dewiki',
					'chd_db' => 'dewiki',
					'chd_seen' => 2,
					'chd_touched' => '20130303000220',
					'chd_lock' => 'LOCK',
					'chd_disabled' => 0,
				),
				array(
					'chd_site' => 'frwiki',
					'chd_db' => 'frwiki',
					'chd_seen' => 1,
					'chd_touched' => '20130303000110',
					'chd_lock' => null,
					'chd_disabled' => 0,
				),
			),
			'changes' => array(
				2 => array(
					'change_id' => 2,
					'change_time' => '20130303000200',
					'change_type' => 'test',
					'change_object_id' => 'test',
				),
				3 => array(
					'change_id' => 3,
					'change_time' => '20130303000300',
					'change_type' => 'test',
					'change_object_id' => 'test',
				),
				1 => array(
					'change_id' => 1,
					'change_time' => '20130303000100',
					'change_type' => 'test',
					'change_object_id' => 'test',
				),
			),

			'now' => '20130303000400',

			'expected' => array(
				'getClientStates' => array(
					array(
						'chd_site' => 'frwiki',
						'chd_seen' => 1,
						'chd_touched' => '20130303000110',
						'chd_untouched' => 170,
						'chd_pending' => 2,
						'chd_lag' => 120,
					),
					array(
						'chd_site' => 'dewiki',
						'chd_seen' => 2,
						'chd_touched' => '20130303000220',
						'chd_untouched' => 100,
						'chd_pending' => 1,
						'chd_lag' => 60,
					),
					array(
						'chd_site' => 'enwiki',
						'chd_seen' => 3,
						'chd_touched' => '20130303000330',
						'chd_untouched' => 30,
						'chd_pending' => 0,
						'chd_lag' => 0,
					),
				),

				'getClientCount' => 3,
				'getLockedCount' => 1,

				'getMaxChangeId' => 3,
				'getMinChangeId' => 1,
				'getMaxChangeTimestamp' => '20130303000300',
				'getMinChangeTimestamp' => '20130303000100',

				'getFreshest' => array(
					'chd_site' => 'enwiki',
					'chd_seen' => 3,
					'chd_touched' => '20130303000330',
					'chd_untouched' => 30,
					'chd_pending' => 0,
					'chd_lag' => 0,
				),
				'getStalest' => array(
					'chd_site' => 'frwiki',
					'chd_seen' => 1,
					'chd_touched' => '20130303000110',
					'chd_untouched' => 170,
					'chd_pending' => 2,
					'chd_lag' => 120,
				),
				'getMedian' => array(
					'chd_site' => 'dewiki',
					'chd_seen' => 2,
					'chd_touched' => '20130303000220',
					'chd_untouched' => 100,
					'chd_pending' => 1,
					'chd_lag' => 60,
				),
				'getAverage' => array(
					'chd_untouched' => 100,
					'chd_pending' => 1,
					'chd_lag' => 60,
				),
			),
		);
	}

	public static function provideGetClientStates() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getClientStates'],
			)
		);
	}

	/**
	 * @dataProvider provideGetClientStates
	 */
	public function testGetClientStates( $expected ) {
		$stats = $this->getDispatchStats();

		$states = $stats->getClientStates();

		$this->assertEquals( count( $expected ), count( $states ), "number of state objects" );

		reset( $expected );
		reset( $states );
		foreach ( $states as $state ) {
			$this->assertStateEquals( current( $expected ), $state );
			next( $expected );
		}
	}

	protected function assertStateEquals( $expected, $actual ) {
		$this->assertInternalType( 'array', $expected );
		$this->assertInternalType( 'object', $actual );

		if ( isset( $expected['site'] ) ) {
			$this->assertEquals( $expected['site'], $actual>chd_site, 'site' );
			$suffix = "/" . $expected['site'];
		} else {
			$suffix = '';
		}

		if ( isset( $expected['seen'] ) ) {
			$this->assertEquals( $expected['seen'], $actual>chd_seen, "seen/$suffix" );
		}

		if ( isset( $expected['touched'] ) ) {
			$this->assertEquals( $expected['touched'], $actual>chd_touched, "touched/$suffix" );
		}

		if ( isset( $expected['lag'] ) ) {
			$this->assertEquals( $expected['lag'], $actual>chd_untouched, "lag/$suffix" );
		}

		if ( isset( $expected['dist'] ) ) {
			$this->assertEquals( $expected['dist'], $actual>chd_pending, "dist/$suffix" );
		}
	}

	public static function provideGetClientCount() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getClientCount'],
			)
		);
	}

	/**
	 * @dataProvider provideGetClientCount
	 */
	public function testGetClientCount( $expected ) {
		$stats = $this->getDispatchStats( );

		$this->assertEquals( $expected, $stats->getClientCount() );
	}

	public static function provideGetLockedCount() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getLockedCount'],
			)
		);
	}

	/**
	 * @dataProvider provideGetLockedCount
	 */
	public function testGetLockedCount( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getLockedCount() );
	}

	public static function provideGetMinChangeId() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getMinChangeId'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMinChangeId
	 */
	public function testGetMinChangeId( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getMinChangeId() );
	}

	public static function provideGetMaxChangeId() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getMaxChangeId'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMaxChangeId
	 */
	public function testGetMaxChangeId( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getMaxChangeId() );
	}

	public static function provideGetMinChangeTimestamp() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getMinChangeTimestamp'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMinChangeTimestamp
	 */
	public function testGetMinChangeTimestamp( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getMinChangeTimestamp() );
	}

	public static function provideGetMaxChangeTimestamp() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getMaxChangeTimestamp'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMaxChangeTimestamp
	 */
	public function testGetMaxChangeTimestamp( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertEquals( $expected, $stats->getMaxChangeTimestamp() );
	}

	public static function provideGetFreshest() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getFreshest'],
			)
		);
	}

	/**
	 * @dataProvider provideGetFreshest
	 */
	public function testGetFreshest( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertStateEquals( $expected, $stats->getFreshest());
	}

	public static function provideGetStalest() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getStalest'],
			)
		);
	}

	/**
	 * @dataProvider provideGetStalest
	 */
	public function testGetStalest( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertStateEquals( $expected, $stats->getStalest());
	}

	public static function provideGetAverage() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getAverage'],
			)
		);
	}

	/**
	 * @dataProvider provideGetAverage
	 */
	public function testGetAverage( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertStateEquals( $expected, $stats->getStalest());
	}

	public static function provideGetMedian() {
		$data = self::getTestData();

		return array(
			array(
				$data['expected']['getMedian'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMedian
	 */
	public function testGetMedian( $expected ) {
		$stats = $this->getDispatchStats();

		$this->assertStateEquals( $expected, $stats->getStalest());
	}
}
