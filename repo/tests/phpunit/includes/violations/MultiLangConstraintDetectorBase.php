<?php

namespace Wikibase\Test;
use Status;
use Wikibase\Item;

/**
 * Tests Wikibase\MultiLangConstraintDetector.
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
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group MultiLangConstraintDetector
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class MultiLangConstraintDetectorBase extends \MediaWikiTestCase {

	static $limit = 23;
	static $short = 'This is a short string'; // 22 bytes
	static $long = 'This is a to long string'; // 24 bytes

	public function mlStringProvider() {
		return array(
			array(
				array( 'en' => self::$short ), // 22 bytes
				array(),
				false
			),
			array(
				array( 'en' => self::$long ), // 24 bytes
				array( 'en' => self::$long ), // 24 bytes
				true
			)
		);
	}

	/**
	 * Create a new detector for this specific type of test
	 */
	static function newDetector() {
		return null;
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function doGetStringLengthConstraintViolations( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$extData = array(
			'da' => self::$short,
			'de' => self::$long,
		);
		$extExpected = array(
			'de' => self::$long,
		);

		$result = call_user_func_array(
			array( static::detectorClass(), 'findLengthConstraintViolations' ),
			array( array_merge( $extData, $data ), static::$limit, $status )
		);

		$this->assertEquals( array_merge( $extExpected, $expected ), $result );
		$this->assertEquals( empty( $result ), $status->isGood() );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function doGetArrayLengthConstraintViolations( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$data = array_map( function($v) { return array($v); }, $data );
		$expected = array_map( function($v) { return array($v); }, $expected );
		$extData = array(
			'da' => array( self::$short ),
			'de' => array( self::$long ),
		);
		$extExpected = array(
			'de' => array( self::$long ),
		);

		$result = call_user_func_array(
			array( static::detectorClass(), 'findLengthConstraintViolations' ),
			array( array_merge_recursive( $extData, $data ), static::$limit, $status )
		);

		//$result = self::findLengthConstraintViolations( array_merge_recursive( $extData, $data ), self::$limit, $status );
		$this->assertEquals( array_merge_recursive( $extExpected, $expected ), $result );
		$this->assertEquals( empty( $result ), $status->isGood() );
	}

}