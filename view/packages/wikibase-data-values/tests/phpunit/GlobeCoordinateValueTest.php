<?php

namespace DataValues\Tests;

use DataValues\GlobeCoordinateValue;
use DataValues\LatLongValue;

/**
 * @covers DataValues\GlobeCoordinateValue
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
class GlobeCoordinateValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\GlobeCoordinateValue';
	}

	public function validConstructorArgumentsProvider() {
		$argLists = array();

		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 1 );
		$argLists[] = array( new LatLongValue( 4.2, 42 ), 1 );
		$argLists[] = array( new LatLongValue( 42, 4.2 ), 0.1 );
		$argLists[] = array( new LatLongValue( 42, 42 ), 0.1 );
		$argLists[] = array( new LatLongValue( -4.2, -4.2 ), 0.1 );
		$argLists[] = array( new LatLongValue( 4.2, -42 ), 0.1 );
		$argLists[] = array( new LatLongValue( -42, 4.2 ), 10 );
		$argLists[] = array( new LatLongValue( 0, 0 ), 0.001 );

		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 1, GlobeCoordinateValue::GLOBE_EARTH );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 1, 'terminus' );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 1, "Schar's World" );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 1, 'coruscant' );

		return $argLists;
	}

	public function invalidConstructorArgumentsProvider() {
		$argLists = array();

		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), null );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 'foo' );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), true );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), array( 1 ) );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), '1' );

		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 1, null );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 1, array( 1 ) );
		$argLists[] = array( new LatLongValue( 4.2, 4.2 ), 1, 1 );

		// TODO: test precisions that are out of the valid range

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param GlobeCoordinateValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetLatitude( GlobeCoordinateValue $geoCoord, array $arguments ) {
		$actual = $geoCoord->getLatitude();

		$this->assertInternalType( 'float', $actual );
		$this->assertEquals( $arguments[0]->getLatitude(), $actual );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param GlobeCoordinateValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetLongitude( GlobeCoordinateValue $geoCoord, array $arguments ) {
		$actual = $geoCoord->getLongitude();

		$this->assertInternalType( 'float', $actual );
		$this->assertEquals( $arguments[0]->getLongitude(), $actual );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param GlobeCoordinateValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetPrecision( GlobeCoordinateValue $geoCoord, array $arguments ) {
		$actual = $geoCoord->getPrecision();

		$this->assertTrue( is_float( $actual ) || is_int( $actual ), 'Precision is int or float' );
		$this->assertEquals( $arguments[1], $actual );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param GlobeCoordinateValue $geoCoord
	 * @param array $arguments
	 */
	public function testGetGlobe( GlobeCoordinateValue $geoCoord, array $arguments ) {
		$expected = array_key_exists( 2, $arguments )
			? $arguments[2]
			: GlobeCoordinateValue::GLOBE_EARTH;

		$actual = $geoCoord->getGlobe();

		$this->assertTrue(
			is_string( $actual ),
			'getGlobe should return a string'
		);

		$this->assertEquals( $expected, $actual );
	}

	public function testArrayValueCompatibility() {
		// These serializations where generated using revision f91f65f989cc3ffacbe924012d8f5b574e0b710c
		// getArrayValue + serialize

		$simpleSerialization = 'a:5:{s:8:"latitude";d:-4.2000000000000002;s:9:"longitude";d:42;s:8:"altitude";N;s:9:"precision";d:0.01;s:5:"globe";s:4:"mars";}';

		$arrayForm = unserialize( $simpleSerialization );
		$geoCoordinate = GlobeCoordinateValue::newFromArray( $arrayForm );

		$this->assertEquals( -4.2, $geoCoordinate->getLatitude() );
		$this->assertEquals( 42, $geoCoordinate->getLongitude() );
		$this->assertEquals( 0.01, $geoCoordinate->getPrecision() );
		$this->assertEquals( 'mars', $geoCoordinate->getGlobe() );

		$simpleSerialization = 'a:5:{s:8:"latitude";d:-4.2000000000000002;s:9:"longitude";d:-42;s:8:"altitude";d:9001;s:9:"precision";d:1;s:5:"globe";s:33:"http://www.wikidata.org/entity/Q2";}';

		$arrayForm = unserialize( $simpleSerialization );
		$geoCoordinate = GlobeCoordinateValue::newFromArray( $arrayForm );

		$this->assertEquals( -4.2, $geoCoordinate->getLatitude() );
		$this->assertEquals( -42, $geoCoordinate->getLongitude() );
		$this->assertEquals( 1, $geoCoordinate->getPrecision() );
		$this->assertEquals( 'http://www.wikidata.org/entity/Q2', $geoCoordinate->getGlobe() );
	}

	// TODO: add compatibility test for using serialize directly

}
