<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueFormatters\TimeFormatter;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\MWTimeIsoParser;

/**
 * @covers \Wikibase\Lib\Parsers\YearMonthTimeParserTest
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

			'1 1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'01 1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'2000 1' =>
				array( '+0000000000002000-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'13 3' =>
				array( '+0000000000000013-03-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'3 13' =>
				array( '+0000000000000013-03-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 13,000' =>
				array( '+0000000000013000-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1-1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1/1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'Jan/1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'January/1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'January/1' =>
				array( '+0000000000000001-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'January-1' =>
				array( '+0000000000000001-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'April-1,000,000,001' =>
				array( '+0000001000000001-04-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'April 1,000,000,001' =>
				array( '+0000001000000001-04-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),
			'1,000,000,001 April' =>
				array( '+0000001000000001-04-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),

		);

		foreach ( $valid as $value => $expected ) {
			// $time, $timezone, $before, $after, $precision, $calendarModel
			$expected = new TimeValue( $expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]  );
			$argLists[] = array( (string)$value, $expected );
		}

		return $argLists;
	}

}