<?php

namespace DataValues\Test;

use DataValues\QuantityValue;

/**
 * Tests for the DataValues\QuantityValue class.
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

		$argLists[] = array( true, 42, 'm' );
		$argLists[] = array( true, -42, 'm' );
		$argLists[] = array( true, 4.2, 'm' );
		$argLists[] = array( true, -4.2, 'm' );
		$argLists[] = array( true, 0, 'm' );
		$argLists[] = array( false, 'foo', 'm' );
		$argLists[] = array( false, '', 'm' );
		$argLists[] = array( false, '0', 'm' );
		$argLists[] = array( false, 42, 0 );
		$argLists[] = array( false, -42, 0 );
		$argLists[] = array( false, 4.2, null );
		$argLists[] = array( false, -4.2, false );
		$argLists[] = array( false, 0, true );
		$argLists[] = array( false, 'foo', array() );
		$argLists[] = array( false, '', 4.2 );
		$argLists[] = array( false, '0', -1 );

		$argLists[] = array( true, 42, 'm', 4.2 );
		$argLists[] = array( true, -42, 'm', -4.2 );
		$argLists[] = array( true, 4.2, 'm', -42 );
		$argLists[] = array( true, -4.2, 'm', 42 );
		$argLists[] = array( false, 42, 'm', false );
		$argLists[] = array( false, -42, 'm', null );
		$argLists[] = array( false, 4.2, 'm', '0' );
		$argLists[] = array( false, -4.2, 'm', '-4.2' );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\QuantityValue $quantity
	 * @param array $arguments
	 */
	public function testGetValue( QuantityValue $quantity, array $arguments ) {
		$this->assertInstanceOf( $this->getClass(), $quantity->getValue() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\QuantityValue $quantity
	 * @param array $arguments
	 */
	public function testGetAmount( QuantityValue $quantity, array $arguments ) {
		$this->assertEquals( $arguments[0], $quantity->getAmount() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\QuantityValue $quantity
	 * @param array $arguments
	 */
	public function testGetUnit( QuantityValue $quantity, array $arguments ) {
		$expected = count( $arguments ) > 1 ? $arguments[1] : null;
		$this->assertEquals( $expected, $quantity->getUnit() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\QuantityValue $quantity
	 * @param array $arguments
	 */
	public function testGetAccuracy( QuantityValue $quantity, array $arguments ) {
		$expected = count( $arguments ) > 2 ? $arguments[2] : null;
		$this->assertEquals( $expected, $quantity->getAccuracy() );
	}

}
