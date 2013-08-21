<?php

namespace DataValues\Tests;

use DataValues\QuantityValue;

/**
 * @covers DataValues\QuantityValue
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
class QuantityValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\QuantityValue';
	}

	public function validConstructorArgumentsProvider() {
		$argLists = array();

		$argLists[] = array( 42 );
		$argLists[] = array( -42 );
		$argLists[] = array( 4.2 );
		$argLists[] = array( -4.2 );
		$argLists[] = array( 0 );

		$argLists[] = array( 42, 'm' );
		$argLists[] = array( -42, 'm' );
		$argLists[] = array( 4.2, 'm' );
		$argLists[] = array( -4.2, 'm' );
		$argLists[] = array( 0, 'm' );

		$argLists[] = array( 4.2, null );

		$argLists[] = array( 42, 'm', 4.2 );
		$argLists[] = array( -42, 'm', -4.2 );
		$argLists[] = array( 4.2, 'm', -42 );
		$argLists[] = array( -4.2, 'm', 42 );
		$argLists[] = array( -42, 'm', null );

		return $argLists;
	}

	public function invalidConstructorArgumentsProvider() {
		$argLists = array();

		$argLists[] = array();


		$argLists[] = array( 'foo' );
		$argLists[] = array( '' );
		$argLists[] = array( '0' );
		$argLists[] = array( '42' );
		$argLists[] = array( '-42' );
		$argLists[] = array( '4.2' );
		$argLists[] = array( '-4.2' );
		$argLists[] = array( false );
		$argLists[] = array( true );
		$argLists[] = array( null );
		$argLists[] = array( '0x20' );

		$argLists[] = array( 'foo', 'm' );
		$argLists[] = array( '', 'm' );
		$argLists[] = array( '0', 'm' );
		$argLists[] = array( 42, 0 );
		$argLists[] = array( -42, 0 );

		$argLists[] = array( -4.2, false );
		$argLists[] = array( 0, true );
		$argLists[] = array( 'foo', array() );
		$argLists[] = array( '', 4.2 );
		$argLists[] = array( '0', -1 );

		$argLists[] = array( 42, 'm', false );
		$argLists[] = array( 4.2, 'm', '0' );
		$argLists[] = array( -4.2, 'm', '-4.2' );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param QuantityValue $quantity
	 * @param array $arguments
	 */
	public function testGetValue( QuantityValue $quantity, array $arguments ) {
		$this->assertInstanceOf( $this->getClass(), $quantity->getValue() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param QuantityValue $quantity
	 * @param array $arguments
	 */
	public function testGetAmount( QuantityValue $quantity, array $arguments ) {
		$this->assertEquals( $arguments[0], $quantity->getAmount() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param QuantityValue $quantity
	 * @param array $arguments
	 */
	public function testGetUnit( QuantityValue $quantity, array $arguments ) {
		$expected = count( $arguments ) > 1 ? $arguments[1] : null;
		$this->assertEquals( $expected, $quantity->getUnit() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param QuantityValue $quantity
	 * @param array $arguments
	 */
	public function testGetAccuracy( QuantityValue $quantity, array $arguments ) {
		$expected = count( $arguments ) > 2 ? $arguments[2] : null;
		$this->assertEquals( $expected, $quantity->getAccuracy() );
	}

}
