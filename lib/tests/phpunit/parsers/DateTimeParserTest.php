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
			'10/10/10' =>
				array( '+0000000000002010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'10/10/2010' =>
				array( '+0000000000002010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'10/10/0010' =>
				array( '+0000000000000010-10-10T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1/1/1' =>
				array( '+0000000000002001-01-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'1-1-1' =>
				array( '+0000000000002001-01-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			//US time style below :O
//			'1/2/3' =>
//				array( '+0000000000002003-02-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
//			'1-2-3' =>
//				array( '+0000000000002003-02-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			//			'Jan 1999' =>
			//				array( '+0000000000001999-01-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 July 2013' =>
				array( '+0000000000002013-07-01T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),
			'9 Jan 09' =>
				array( '+0000000000002009-01-09T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_DAY , TimeFormatter::CALENDAR_GREGORIAN ),

		);

		foreach ( $valid as $value => $expected ) {
			// $time, $timezone, $before, $after, $precision, $calendarModel
			$expected = new TimeValue( $expected[0], $expected[1], $expected[2], $expected[3], $expected[4], $expected[5]  );
			$argLists[] = array( (string)$value, $expected );
		}

		return $argLists;
	}

}