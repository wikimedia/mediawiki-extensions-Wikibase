<?php

namespace DataValues\Test;

use DataValues\NumberValue;

/**
 * Tests for the DataValues\NumberValue class.
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
class NumberValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\NumberValue';
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
		$argLists[] = array( true, 42 );
		$argLists[] = array( true, -42 );
		$argLists[] = array( true, 4.2 );
		$argLists[] = array( true, -4.2 );
		$argLists[] = array( true, 0 );
		$argLists[] = array( false, 'foo' );
		$argLists[] = array( false, '' );
		$argLists[] = array( false, '0' );
		$argLists[] = array( false, '42' );
		$argLists[] = array( false, '-42' );
		$argLists[] = array( false, '4.2' );
		$argLists[] = array( false, '-4.2' );
		$argLists[] = array( false, false );
		$argLists[] = array( false, true );
		$argLists[] = array( false, null );
		$argLists[] = array( false, '0x20' );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\NumberValue $number
	 * @param array $arguments
	 */
	public function testGetValue( NumberValue $number, array $arguments ) {
		$this->assertEquals( $arguments[0], $number->getValue() );
	}

}
