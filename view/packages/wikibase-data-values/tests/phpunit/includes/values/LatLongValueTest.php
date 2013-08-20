<?php

namespace DataValues\Test;

use DataValues\GlobeCoordinateValue;
use DataValues\LatLongValue;

/**
 * @covers DataValues\LatLongValue
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
class LatLongValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\LatLongValue';
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

		$argLists[] = array( false );
		$argLists[] = array( false, 42 );
		$argLists[] = array( false, array() );
		$argLists[] = array( false, false );
		$argLists[] = array( false, true );
		$argLists[] = array( false, null );
		$argLists[] = array( false, 'foo' );
		$argLists[] = array( false, 42 );

		$argLists[] = array( false, 'en', 42 );
		$argLists[] = array( false, 'en', 4.2 );
		$argLists[] = array( false, 42, false );
		$argLists[] = array( false, 42, array() );
		$argLists[] = array( false, 42, null );
		$argLists[] = array( false, 42, 'foo' );
		$argLists[] = array( false, 4.2, 'foo' );

		$argLists[] = array( false, '4.2', 4.2 );
		$argLists[] = array( false, '4.2', '4.2' );
		$argLists[] = array( false, 4.2, '4.2' );
		$argLists[] = array( false, '42', 42 );
		$argLists[] = array( false, 42, '42' );
		$argLists[] = array( false, '0', 0 );

		$argLists[] = array( false, -91, 0 );
		$argLists[] = array( false, -999, 1 );
		$argLists[] = array( false, 90.001, 2 );
		$argLists[] = array( false, 3, 181 );
		$argLists[] = array( false, 4, -1337 );

		$argLists[] = array( true, 4.2, 4.2 );
		$argLists[] = array( true, 4.2, 42 );
		$argLists[] = array( true, 42, 4.2 );
		$argLists[] = array( true, 42, 42 );
		$argLists[] = array( true, -4.2, -4.2 );
		$argLists[] = array( true, 4.2, -42 );
		$argLists[] = array( true, -42, 4.2 );
		$argLists[] = array( true, 0, 0 );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param LatLongValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetLatitude( LatLongValue $geoCoord, array $arguments ) {
		$actual = $geoCoord->getLatitude();

		$this->assertInternalType( 'float', $actual );
		$this->assertEquals( (float)$arguments[0], $actual );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param LatLongValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetLongitude( LatLongValue $geoCoord, array $arguments ) {
		$actual = $geoCoord->getLongitude();

		$this->assertInternalType( 'float', $actual );
		$this->assertEquals( (float)$arguments[1], $actual );
	}

}
