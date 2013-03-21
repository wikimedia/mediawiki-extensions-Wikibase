<?php
namespace Wikibase\Test;
use Wikibase\DispatchStats;

/**
 * Tests for the Wikibase\EntityChange class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityChangeTest extends \MediaWikiTestCase {

	/**
	 * Injects the given $changes and $states into the database,
	 * then creates and loads a DispatchStats object.
	 *
	 * @param array $changes
	 * @param array $states
	 * @param string|int $now the value of now.
	 *
	 * @return DispatchStats
	 */
	protected function getDispatchStats( $changes, $states ) {
		$dbw = wfGetDB( DB_MASTER ); // write to dummy tables

		$dbw->query( "truncate " . $dbw->tableName( 'wb_changes' ) );
		$dbw->query( "truncate " . $dbw->tableName( 'wb_changes_dispatch' ) );

		foreach ( $changes as $row ) {
			$dbw->insert( 'wb_changes',
				$row,
				__METHOD__
			);
		}

		foreach ( $states as $row ) {
			$dbw->insert( 'wb_changes_dispatch',
				$row,
				__METHOD__
			);
		}

		$stats = new DispatchStats();
		$stats->load();
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
				array(
					'change_id' => 2,
					'change_time' => '20130303000200',
					'change_type' => 'test',
					'change_object_id' => 'test',
				),
				array(
					'change_id' => 3,
					'change_time' => '20130303000300',
					'change_type' => 'test',
					'change_object_id' => 'test',
				),
				array(
					'change_id' => 1,
					'change_time' => '20130303000100',
					'change_type' => 'test',
					'change_object_id' => 'test',
				),
			),

			'expected' => array(
				'getClientStates' => array(
					array(
						'chd_site' => 'frwiki',
						'chd_seen' => 1,
						'chd_touched' => '20130303000110',
						'chd_lag' => 110,
						'chd_dist' => 2,
					),
					array(
						'chd_site' => 'dewiki',
						'chd_seen' => 2,
						'chd_touched' => '20130303000220',
						'chd_lag' => 40,
						'chd_dist' => 1,
					),
					array(
						'chd_site' => 'enwiki',
						'chd_seen' => 3,
						'chd_touched' => '20130303000330',
						'chd_lag' => 0,
						'chd_dist' => 0,
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
					'chd_lag' => 0,
					'chd_dist' => 0,
				),
				'getStalest' => array(
					'chd_site' => 'frwiki',
					'chd_seen' => 1,
					'chd_touched' => '20130303000110',
					'chd_lag' => 110,
					'chd_dist' => 2,
				),
				'getMedian' => array(
					'chd_site' => 'dewiki',
					'chd_seen' => 2,
					'chd_touched' => '20130303000220',
					'chd_lag' => 40,
					'chd_dist' => 1,
				),
				'getAverage' => array(
					'chd_lag' => 30,
					'chd_dist' => 1,
				),
			),
		);
	}

	public static function provideGetClientStates() {
		$data = self::getTestData();

		return array(
			array(
				$data['changes'],
				$data['states'],
				$data['expected']['getClientStates'],
			)
		);
	}

	/**
	 * @dataProvider provideGetClientStates
	 */
	public function testGetClientStates( $changes, $states, $expected ) {
		$stats = $this->getDispatchStats( $changes, $states );

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
		$this->assertType( 'array', $expected );
		$this->assertType( 'object', $actual );

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
			$this->assertEquals( $expected['lag'], $actual>chd_lag, "lag/$suffix" );
		}

		if ( isset( $expected['dist'] ) ) {
			$this->assertEquals( $expected['dist'], $actual>chd_dist, "dist/$suffix" );
		}
	}

	public static function provideGetClientCount() {
		$data = self::getTestData();

		return array(
			array(
				$data['states'],
				$data['expected']['getClientCount'],
			)
		);
	}

	/**
	 * @dataProvider provideGetClientCount
	 */
	public function testGetClientCount( $states, $expected ) {
		$stats = $this->getDispatchStats( array(), $states );

		$this->assertEquals( $expected, $stats->getClientCount() );
	}

	public static function provideGetLockedCount() {
		$data = self::getTestData();

		return array(
			array(
				$data['states'],
				$data['expected']['getLockedCount'],
			)
		);
	}

	/**
	 * @dataProvider provideGetLockedCount
	 */
	public function testGetLockedCount( $states, $expected ) {
		$stats = $this->getDispatchStats( array(), $states );

		$this->assertEquals( $expected, $stats->getLockedCount() );
	}

	public static function provideGetMinChangeId() {
		$data = self::getTestData();

		return array(
			array(
				$data['changes'],
				$data['expected']['getMinChangeId'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMinChangeId
	 */
	public function testGetMinChangeId( $changes, $expected ) {
		$stats = $this->getDispatchStats( $changes, array() );

		$this->assertEquals( $expected, $stats->getMinChangeId() );
	}

	public static function provideGetMaxChangeId() {
		$data = self::getTestData();

		return array(
			array(
				$data['changes'],
				$data['expected']['getMaxChangeId'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMaxChangeId
	 */
	public function testGetMaxChangeId( $changes, $expected ) {
		$stats = $this->getDispatchStats( $changes, array() );

		$this->assertEquals( $expected, $stats->getMaxChangeId() );
	}

	public static function provideGetMinChangeTimestamp() {
		$data = self::getTestData();

		return array(
			array(
				$data['changes'],
				$data['expected']['getMinChangeTimestamp'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMinChangeTimestamp
	 */
	public function testGetMinChangeTimestamp( $changes, $expected ) {
		$stats = $this->getDispatchStats( $changes, array() );

		$this->assertEquals( $expected, $stats->getMinChangeTimestamp() );
	}

	public static function provideGetMaxChangeTimestamp() {
		$data = self::getTestData();

		return array(
			array(
				$data['changes'],
				$data['expected']['getMaxChangeTimestamp'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMaxChangeTimestamp
	 */
	public function testGetMaxChangeTimestamp( $changes, $expected ) {
		$stats = $this->getDispatchStats( $changes, array() );

		$this->assertEquals( $expected, $stats->getMaxChangeTimestamp() );
	}

	public static function provideGetFreshest() {
		$data = self::getTestData();

		return array(
			array(
				$data['states'],
				$data['expected']['getFreshest'],
			)
		);
	}

	/**
	 * @dataProvider provideGetFreshest
	 */
	public function testGetFreshest( $states, $expected ) {
		$stats = $this->getDispatchStats( array(), $states );

		$this->assertStateEquals( $expected, $stats->getFreshest());
	}

	public static function provideGetStalest() {
		$data = self::getTestData();

		return array(
			array(
				$data['states'],
				$data['expected']['getStalest'],
			)
		);
	}

	/**
	 * @dataProvider provideGetStalest
	 */
	public function testGetStalest( $states, $expected ) {
		$stats = $this->getDispatchStats( array(), $states );

		$this->assertStateEquals( $expected, $stats->getStalest());
	}

	public static function provideGetAverage() {
		$data = self::getTestData();

		return array(
			array(
				$data['states'],
				$data['expected']['getAverage'],
			)
		);
	}

	/**
	 * @dataProvider provideGetAverage
	 */
	public function testGetAverage( $states, $expected ) {
		$stats = $this->getDispatchStats( array(), $states );

		$this->assertStateEquals( $expected, $stats->getStalest());
	}

	public static function provideGetMedian() {
		$data = self::getTestData();

		return array(
			array(
				$data['states'],
				$data['expected']['getMedian'],
			)
		);
	}

	/**
	 * @dataProvider provideGetMedian
	 */
	public function testGetMedian( $states, $expected ) {
		$stats = $this->getDispatchStats( array(), $states );

		$this->assertStateEquals( $expected, $stats->getStalest());
	}
}