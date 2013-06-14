<?php

namespace DataValues\Test;

use DataValues\GeoCoordinateValue;

/**
 * Tests for the DataValues\GeoCoordinateValue class.
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
 * @since 0.1
 *
 * @ingroup DataValue
 *
 * @group DataValue
 * @group DataValueExtensions
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class GeoCoordinateValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\GeoCoordinateValue';
	}

	/**
	 * @see DataValueTest::constructorProvider
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function constructorProvider() {
		$argLists = array();

		// #0
		$argLists[] = array( false );
		$argLists[] = array( false, 42 );
		$argLists[] = array( false, array() );
		$argLists[] = array( false, false );
		$argLists[] = array( false, true );
		$argLists[] = array( false, null );
		$argLists[] = array( false, 'foo' );
		$argLists[] = array( false, 42 );

		// #8
		$argLists[] = array( false, 'en', 42 );
		$argLists[] = array( false, 'en', 4.2 );
		$argLists[] = array( false, 42, false );
		$argLists[] = array( false, 42, array() );
		$argLists[] = array( false, 42, null );
		$argLists[] = array( false, 42, 'foo' );
		$argLists[] = array( false, 4.2, 'foo' );

		// #15
		$argLists[] = array( true, 4.2, 4.2 );
		$argLists[] = array( true, 4.2, 42 );
		$argLists[] = array( true, 42, 4.2 );
		$argLists[] = array( true, 42, 42 );
		$argLists[] = array( true, -4.2, -4.2 );
		$argLists[] = array( true, 4.2, -42 );
		$argLists[] = array( true, -42, 4.2 );
		$argLists[] = array( true, 0, 0 );

		// #23
		$argLists[] = array( false, '4.2', 4.2 );
		$argLists[] = array( false, '4.2', '4.2' );
		$argLists[] = array( false, 4.2, '4.2' );
		$argLists[] = array( false, '42', 42 );
		$argLists[] = array( false, 42, '42' );
		$argLists[] = array( false, '0', 0 );

		// #29
		$argLists[] = array( true, 4.2, 4.2, 4.2 );
		$argLists[] = array( true, 4.2, -4.2, -42 );
		$argLists[] = array( true, -42, 4.2, 42 );
		$argLists[] = array( true, -4.2, 42, -4.2 );
		$argLists[] = array( true, 0, 0, 0 );

		// #34
		$argLists[] = array( false, 4.2, 4.2, '4.2' );
		$argLists[] = array( false, 4.2, '4.2', 4.2 );
		$argLists[] = array( false, '4.2', 4.2, 4.2 );
		$argLists[] = array( false, 42, 4.2, false );
		$argLists[] = array( false, 42, 4.2, true );
		$argLists[] = array( true, 42, 4.2, null );
		$argLists[] = array( false, 42, 4.2, array() );
		$argLists[] = array( false, 42, 4.2, 'foo' );

		// #42
		$argLists[] = array( true, 42, 4.2, 9000.1, null, 'http://www.wikidata.org/entity/Q2' );
		$argLists[] = array( true, 42, 4.2, 9000.1, null );
		$argLists[] = array( true, 4.2, 42, 9000.1, null, 'terminus' );
		$argLists[] = array( true, 4.2, 42, 0, null, "Schar's World" );
		$argLists[] = array( true, -42, -4.2, -9000.1, null, 'coruscant' );

		// #47
		$argLists[] = array( false, 42, 4.2, 9000.1, null, false );
		$argLists[] = array( false, 42, 4.2, 9000.1, null, true );
		$argLists[] = array( false, 42, 4.2, 9000.1, null, 42 );
		$argLists[] = array( false, 42, 4.2, 9000.1, null, 4.2 );
		$argLists[] = array( false, 42, 4.2, 9000.1, null, -1 );
		$argLists[] = array( false, 42, 4.2, 9000.1, null, 0 );
		$argLists[] = array( false, 42, 4.2, 9000.1, null, array() );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\GeoCoordinateValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetLatitude( GeoCoordinateValue $geoCoord, array $arguments ) {
		$actual = $geoCoord->getLatitude();

		$this->assertInternalType( 'float', $actual );
		$this->assertEquals( (float)$arguments[0], $actual );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\GeoCoordinateValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetLongitude( GeoCoordinateValue $geoCoord, array $arguments ) {
		$actual = $geoCoord->getLongitude();

		$this->assertInternalType( 'float', $actual );
		$this->assertEquals( (float)$arguments[1], $actual );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\GeoCoordinateValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetAltitude( GeoCoordinateValue $geoCoord, array $arguments ) {
		$expected = array_key_exists( 2, $arguments ) ? (float)$arguments[2] : null;
		$actual = $geoCoord->getAltitude();

		$this->assertTrue(
			is_null( $actual ) || is_float( $actual ),
			'getAltitude should return a float or null'
		);

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\GeoCoordinateValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetGlobe( GeoCoordinateValue $geoCoord, array $arguments ) {
		$expected = array_key_exists( 4, $arguments )
			? $arguments[4]
			: 'http://www.wikidata.org/entity/Q2';

		$actual = $geoCoord->getGlobe();

		$this->assertTrue(
			is_string( $actual ),
			'getGlobe should return a string'
		);

		$this->assertEquals( $expected, $actual );
	}

}
