<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueFormatters\TimeFormatter;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\MWTimeIsoParser;

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
	 * @return MWTimeIsoParser
	 */
	protected function getInstance() {
		$class = $this->getParserClass();
		return new $class( $this->newParserOptions() );
	}

	/**
	 * @return string
	 */
	protected function getParserClass() {
		return 'Wikibase\Lib\Parsers\YearMonthTimeParser';
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = array();

		$valid = array(
			// leading zeros
			'00001 1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'000000001 100001999' =>
				array( '+0000000100001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),

			// use string month names
			'Jan/1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'January/1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'January/1' =>
				array( '+0000000000000001-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'January-1' =>
				array( '+0000000000000001-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'JanuARY-1' =>
				array( '+0000000000000001-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'JaN/1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'januARY-1' =>
				array( '+0000000000000001-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'jan/1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),

			// use different date separators
			'1-1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1/1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 / 1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1,1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1.1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1. 1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),

			// presume mm/yy unless impossible month, in which case switch
			'12/12' =>
				array( '+0000000000000012-12-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'12/11' =>
				array( '+0000000000000011-12-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'11/12' =>
				array( '+0000000000000012-11-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'13/12' =>
				array( '+0000000000000013-12-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'12/13' =>
				array( '+0000000000000013-12-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'2000 1' =>
				array( '+0000000000002000-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),

			// big years
			'April-1000000001' =>
				array( '+0000001000000001-04-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'April 1000000001' =>
				array( '+0000001000000001-04-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1000000001 April' =>
				array( '+0000001000000001-04-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 13000' =>
				array( '+0000000000013000-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),

			// parse 0 month as if no month has been entered
			'0.1999' =>
				array( '+0000000000001999-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'1999 0' =>
				array( '+0000000000001999-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),

		);

		foreach ( $valid as $value => $expected ) {
			// $time, $timezone, $before, $after, $precision, $calendarModel
			$expected = new TimeValue( $expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]  );
			$argLists[] = array( (string)$value, $expected );
		}

		return $argLists;
	}

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