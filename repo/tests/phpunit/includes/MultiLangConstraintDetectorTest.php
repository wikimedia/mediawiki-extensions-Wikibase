<?php

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
class MultiLangConstraintDetectorTest extends \MediaWikiTestCase {

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
	 * @dataProvider mlStringProvider
	 */
	public function testGetStringLengthConstraintViolations( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$extData = array(
			'da' => self::$short,
			'de' => self::$long,
		);
		$extExpected = array(
			'de' => self::$long,
		);
		$detector = new Wikibase\MultiLangConstraintDetector();
		$result = $detector->getLengthConstraintViolations( array_merge( $extData, $data ), self::$limit, $status );
		$this->assertEquals( array_merge( $extExpected, $expected ), $result );
		$this->assertEquals( empty( $result ), $status->isGood() );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testGetArrayLengthConstraintViolations( $data, $expected, $fatal ) {
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
		$detector = new Wikibase\MultiLangConstraintDetector();
		$result = $detector->getLengthConstraintViolations( array_merge_recursive( $extData, $data ), self::$limit, $status );
		$this->assertEquals( array_merge_recursive( $extExpected, $expected ), $result );
		$this->assertEquals( empty( $result ), $status->isGood() );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testAddStringConstraintChecks( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$detector = new Wikibase\MultiLangConstraintDetector();

		$extData = array(
			'da' => self::$short,
			'de' => self::$long,
		);

		// test labels ---------------
		$baseEntity = new Wikibase\Item( array( 'label' => $extData ) );
		$newEntity = new Wikibase\Item( array( 'label' => array_merge( $extData, $data ) ) );
		$diff = $baseEntity->getDiff( $newEntity );
		$detector->addConstraintChecks( $newEntity, $status, $diff, array( 'length' => self::$limit ) );

		$this->assertEquals( $fatal, !$status->isOk() );

		// test descriptions ---------------
		$baseEntity = new Wikibase\Item( array( 'description' => $extData ) );
		$newEntity = new Wikibase\Item( array( 'description' => array_merge( $extData, $data ) ) );
		$diff = $baseEntity->getDiff( $newEntity );
		$detector->addConstraintChecks( $newEntity, $status, $diff, array( 'length' => self::$limit ) );

		$this->assertEquals( $fatal, !$status->isOk() );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testAddArrayConstraintChecks( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$detector = new Wikibase\MultiLangConstraintDetector();

		$data = array_map( function($v) { return array($v); }, $data );

		$extData = array(
			'da' => array( self::$short ),
			'de' => array( self::$long ),
		);

		// test aliases ---------------
		$baseEntity = new Wikibase\Item( array( 'aliases' => $extData ) );
		$newEntity = new Wikibase\Item( array( 'aliases' => array_merge_recursive( $extData, $data ) ) );
		$diff = $baseEntity->getDiff( $newEntity );
		$detector->addConstraintChecks( $newEntity, $status, $diff, array( 'length' => self::$limit ) );

		$this->assertEquals( $fatal, !$status->isOk() );

	}
}