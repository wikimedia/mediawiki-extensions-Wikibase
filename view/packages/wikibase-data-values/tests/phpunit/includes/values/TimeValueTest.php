<?php

namespace DataValues\Test;

use DataValues\TimeValue;

/**
 * @covers DataValues\TimeValue
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
			333,
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

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'bla',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013/01/01 00:00:00',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-22-01T00:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-35T00:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T27:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:66:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:66Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			'DataValues\IllegalValueException',
			'+00000002013-01-01T00:00:00+60',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			true,
			'+2013-01-01T00:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		$argLists[] = array(
			true,
			'-5-01-01T00:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php',
		);

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param TimeValue $time
	 * @param array $arguments
	 */
	public function testGetTime( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[0], $time->getTime() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param TimeValue $time
	 * @param array $arguments
	 */
	public function testGetTimezone( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[1], $time->getTimezone() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param TimeValue $time
	 * @param array $arguments
	 */
	public function testGetBefore( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[2], $time->getBefore() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param TimeValue $time
	 * @param array $arguments
	 */
	public function testGetAfter( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[3], $time->getAfter() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param TimeValue $time
	 * @param array $arguments
	 */
	public function testGetPrecision( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[4], $time->getPrecision() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param TimeValue $time
	 * @param array $arguments
	 */
	public function testGetCalendarModel( TimeValue $time, array $arguments ) {
		$this->assertEquals( $arguments[5], $time->getCalendarModel() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param TimeValue $time
	 * @param array $arguments
	 */
	public function testGetValue( TimeValue $time, array $arguments ) {
		$this->assertTrue( $time->equals( $time->getValue() ) );
	}

}
