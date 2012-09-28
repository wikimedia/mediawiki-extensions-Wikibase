<?php

namespace DataValues\Test;

/**
 * Tests for the DataValues\NumberValue class.
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

}
