<?php

namespace DataValues\Tests;

use DataValues\UnknownValue;

/**
 * @covers DataValues\UnknownValue
 *
 * @file
 * @since 0.1
 *
 * @ingroup DataValue
 *
 * @group DataValue
 * @group DataValueExtensions
 * @group UnknownValueTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class UnknownValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\UnknownValue';
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
		$argLists[] = array( true, array() );
		$argLists[] = array( true, false );
		$argLists[] = array( true, true );
		$argLists[] = array( true, null );
		$argLists[] = array( true, 'foo' );
		$argLists[] = array( true, '' );
		$argLists[] = array( true, ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param UnknownValue $value
	 * @param array $arguments
	 */
	public function testGetValue( UnknownValue $value, array $arguments ) {
		$this->assertEquals( $arguments[0], $value->getValue() );
	}

}
