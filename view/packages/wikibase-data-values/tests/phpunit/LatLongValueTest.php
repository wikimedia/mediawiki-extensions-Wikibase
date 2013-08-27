<?php

namespace DataValues\Tests;

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

	public function validConstructorArgumentsProvider() {
		$argLists = array();

		$argLists[] = array( 4.2, 4.2 );
		$argLists[] = array( 4.2, 42 );
		$argLists[] = array( 42, 4.2 );
		$argLists[] = array( 42, 42 );
		$argLists[] = array( -4.2, -4.2 );
		$argLists[] = array( 4.2, -42 );
		$argLists[] = array( -42, 4.2 );
		$argLists[] = array( 360, -360 );
		$argLists[] = array( 48.269, -225.99 );
		$argLists[] = array( 0, 0 );

		return $argLists;
	}

	public function invalidConstructorArgumentsProvider() {
		$argLists = array();

		$argLists[] = array();

		$argLists[] = array( 42 );
		$argLists[] = array( array() );
		$argLists[] = array( false );
		$argLists[] = array( true );
		$argLists[] = array( null );
		$argLists[] = array( 'foo' );
		$argLists[] = array( 42 );

		$argLists[] = array( 'en', 42 );
		$argLists[] = array( 'en', 4.2 );
		$argLists[] = array( 42, false );
		$argLists[] = array( 42, array() );
		$argLists[] = array( 42, null );
		$argLists[] = array( 42, 'foo' );
		$argLists[] = array( 4.2, 'foo' );

		$argLists[] = array( '4.2', 4.2 );
		$argLists[] = array( '4.2', '4.2' );
		$argLists[] = array( 4.2, '4.2' );
		$argLists[] = array( '42', 42 );
		$argLists[] = array( 42, '42' );
		$argLists[] = array( '0', 0 );

		$argLists[] = array( -361, 0 );
		$argLists[] = array( -999, 1 );
		$argLists[] = array( 360.001, 2 );
		$argLists[] = array( 3, 361 );
		$argLists[] = array( 4, -1337 );

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
