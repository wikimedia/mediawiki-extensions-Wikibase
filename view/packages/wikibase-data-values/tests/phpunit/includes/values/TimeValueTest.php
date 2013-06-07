<?php

namespace DataValues\Test;

use DataValues\TimeValue;

/**
 * Tests for the DataValues\TimeValue class.
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
class TimeValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\TimeValue';
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

		$argLists[] = array(
			true,
			'+00000002013-01-01T00:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			true,
			'+00000002013-01-01T00:00:00Z',
			7200,
			9001,
			9001,
			TimeValue::PRECISION_Ga,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			true,
			'+00000002013-01-01T00:00:00Z',
			-7200,
			0,
			42,
			TimeValue::PRECISION_YEAR,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:00Z',
			'0',
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:00Z',
			4.2,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:00Z',
			-20 * 3600,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:00Z',
			0,
			0,
			0,
			0,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:00Z',
			0,
			0,
			0,
			9001,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			42,
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:00Z',
			0,
			4.2,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:00Z',
			0,
			0,
			-1,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\TimeValue $time
	 * @param array $arguments
	 */
	public function testGetTime( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[0], $time->getTime() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\TimeValue $time
	 * @param array $arguments
	 */
	public function testGetTimezone( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[1], $time->getTimezone() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\TimeValue $time
	 * @param array $arguments
	 */
	public function testGetBefore( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[2], $time->getBefore() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\TimeValue $time
	 * @param array $arguments
	 */
	public function testGetAfter( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[3], $time->getAfter() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\TimeValue $time
	 * @param array $arguments
	 */
	public function testGetPrecision( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[4], $time->getPrecision() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\TimeValue $time
	 * @param array $arguments
	 */
	public function testGetCalendarModel( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[5], $time->getCalendarModel() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\TimeValue $time
	 * @param array $arguments
	 */
	public function testGetValue( TimeValue $time, array $arguments ) {
		$this->assertTrue( $time->equals( $time->getValue() ) );
	}

}
