<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueFormatters\TimeFormatter;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\MWTimeIsoParser;

/**
 * @covers Wikibase\Lib\Parsers\DateTimeParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class DateTimeParserTest extends StringValueParserTest {

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
		return 'Wikibase\Lib\Parsers\DateTimeParser';
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = array();

		$valid = array(

			// Normal / easy dates
			'10/10/2010' =>
				array( '+0000000000002010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'10.10.2010' =>
				array( '+0000000000002010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'10 10 2010' =>
				array( '+0000000000002010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'10/10/0010' =>
				array( '+0000000000000010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 July 2013' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 Jul 2013' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'January 9 1920' =>
				array( '+0000000000001920-01-09T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'Feb 11 1930' =>
				array( '+0000000000001930-02-11T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1st July 2013' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'2nd July 2013' =>
				array( '+0000000000002013-07-02T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'3rd July 2013' =>
				array( '+0000000000002013-07-03T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1th July 2013' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'2th July 2013' =>
				array( '+0000000000002013-07-02T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'3th July 2013' =>
				array( '+0000000000002013-07-03T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'4th July 2013' =>
				array( '+0000000000002013-07-04T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),

			//Year first dates
			'2009-01-09' =>
				array( '+0000000000002009-01-09T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'55-01-09' =>
				array( '+0000000000000055-01-09T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'555-01-09' =>
				array( '+0000000000000555-01-09T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'33300-1-1' =>
				array( '+0000000000033300-01-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'3330002-1-1' =>
				array( '+0000000003330002-01-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),

			//Less than 4 digit years
			'10/10/10' =>
				array( '+0000000000000010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'9 Jan 09' =>
				array( '+0000000000000009-01-09T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1/1/1' =>
				array( '+0000000000000001-01-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1-1-1' =>
				array( '+0000000000000001-01-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'31-1-55' =>
				array( '+0000000000000055-01-31T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'10-10-100' =>
				array( '+0000000000000100-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'4th July 11' =>
				array( '+0000000000000011-07-04T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'4th July 111' =>
				array( '+0000000000000111-07-04T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'4th July 1' =>
				array( '+0000000000000001-07-04T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),

			//More than 4 digit years
			'4th July 10000' =>
				array( '+0000000000010000-07-04T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'10/10/22000' =>
				array( '+0000000000022000-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1-1-33300' =>
				array( '+0000000000033300-01-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'4th July 7214614279199781' =>
				array( '+7214614279199781-07-04T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),

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
			// These are just wrong!
			'June June June',
			'111 111 111',
			'Jann 2014',

			// Not within the scope of this parser
			'100BC', // The DateTime object cant parse BC years
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}