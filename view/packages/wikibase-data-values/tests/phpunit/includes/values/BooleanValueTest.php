<?php

namespace DataValues\Test;

use DataValues\BooleanValue;

/**
 * Tests for the DataValues\BooleanValue class.
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
class BooleanValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\BooleanValue';
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
		$argLists[] = array( false, '1' );
		$argLists[] = array( false, '' );
		$argLists[] = array( false, 0 );
		$argLists[] = array( false, 1 );
		$argLists[] = array( false, 'foo' );
		$argLists[] = array( false, null );
		$argLists[] = array( true, false );
		$argLists[] = array( true, true );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\BooleanValue $boolean
	 * @param array $arguments
	 */
	public function testGetValue( BooleanValue $boolean, array $arguments ) {
		$this->assertEquals( $arguments[0], $boolean->getValue() );
	}

}
