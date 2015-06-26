<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\YearMonthTimeParser;

/**
 * @covers \Wikibase\Lib\Parsers\YearMonthTimeParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class YearMonthTimeParserTest extends StringValueParserTest {

	/**
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return YearMonthTimeParser
	 */
	protected function getInstance() {
		return new YearMonthTimeParser();
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 */
	public function validInputProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$julian = 'http://www.wikidata.org/entity/Q1985786';

		$argLists = array();

		$valid = array(
			// leading zeros
			'00001 1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'000000001 100001999' =>
				array( '+100001999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),

			// use string month names
			'Jan/1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'January/1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'January/1' =>
				array( '+0001-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'1999 January' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'January 1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'January-1' =>
				array( '+0001-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'JanuARY-1' =>
				array( '+0001-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'JaN/1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'januARY-1' =>
				array( '+0001-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'jan/1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),

			// use different date separators
			'1-1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'1/1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'1 / 1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'1 1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'1,1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'1.1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'1. 1999' =>
				array( '+1999-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),

			// presume mm/yy unless impossible month, in which case switch
			'12/12' =>
				array( '+0012-12-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'12/11' =>
				array( '+0011-12-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'11/12' =>
				array( '+0012-11-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'13/12' =>
				array( '+0013-12-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'12/13' =>
				array( '+0013-12-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $julian ),
			'2000 1' =>
				array( '+2000-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),

			// big years
			'April-1000000001' =>
				array( '+1000000001-04-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'April 1000000001' =>
				array( '+1000000001-04-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'1000000001 April' =>
				array( '+1000000001-04-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),
			'1 13000' =>
				array( '+13000-01-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_MONTH, $gregorian ),

			// parse 0 month as if no month has been entered
			'0.1999' =>
				array( '+1999-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
			'1999 0' =>
				array( '+1999-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
		);

		foreach ( $valid as $value => $expected ) {
			// $time, $timezone, $before, $after, $precision, $calendarModel
			$expected = new TimeValue( $expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]  );
			$argLists[] = array( (string)$value, $expected );
		}

		return $argLists;
	}

	/**
	 * @see StringValueParserTest::invalidInputProvider
	 */
	public function invalidInputProvider() {
		$argLists = parent::invalidInputProvider();

		$invalid = array(
			//These are just wrong!
			'June June June',
			'111 111 111',
			'Jann 2014',
			'13/13',
			'13,1999',
			'1999,13',

			//Dont parse stuff with separators in the year
			'june 200,000,000',
			'june 200.000.000',

			//Not within the scope of this parser
			'1 July 20000',
			'20000',
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}
