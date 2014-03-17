<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueFormatters\TimeFormatter;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\MWTimeIsoParser;

/**
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TimeParserTest extends StringValueParserTest {

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
		return 'Wikibase\Lib\Parsers\TimeParser';
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = array();

		$valid = array(

			//Wikibase\Lib\YearTimeParser
			'1999' =>
				array( '+0000000000001999-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'1' =>
				array( '+0000000000000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'-1000000001' =>
				array( '-0000001000000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'+1000000001' =>
				array( '+0000001000000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'1BC' =>
				array( '-0000000000000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'1CE' =>
				array( '+0000000000000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),

			//Wikibase\Lib\YearMonthTimeParser
			'1 1999' =>
				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_MONTH , TimeFormatter::CALENDAR_GREGORIAN ),

			//ValueParsers\TimeParser
			'+0000000000000000-01-01T00:00:00Z (Gregorian)' =>
				array( '+0000000000000000-01-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'+0-00-20T00:00:00Z' =>
				array( '+0000000000000000-00-20T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),

			//Wikibase\Lib\ParsersMwTimeIsoParser
			'in 13 billion years' =>
				array( '+0000013000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 13,000 million years' =>
				array( '+0000013000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'13,000 million years ago' =>
				array( '-0000013000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),

			//Wikibase\Lib\DateTimeParser
			'10/10/10' =>
				array( '+0000000000000010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 July 2013' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 Jul 2013' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 Jul 2013 BC' =>
				array( '-0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 Jul 2013CE' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'+1 Jul 2013' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'-1 Jul 2013' =>
				array( '-0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),

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
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}