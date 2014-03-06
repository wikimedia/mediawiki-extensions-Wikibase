<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueFormatters\TimeFormatter;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\MWTimeIsoParser;

/**
 * @covers Wikibase\Lib\Parsers\YearTimeParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class YearTimeParserTest extends StringValueParserTest {

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
		return 'Wikibase\Lib\Parsers\YearTimeParser';
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = array();

		$valid = array(
			'1999' =>
				array( '+0000000000001999-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'2000000' =>
				array( '+0000000002000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'2000000000' =>
				array( '+0000002000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'2000020000' =>
				array( '+0000002000020000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_10ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'2000001' =>
				array( '+0000000002000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'02000001' =>
				array( '+0000000002000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'1' =>
				array( '+0000000000000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'000000001' =>
				array( '+0000000000000001-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'+1999' =>
				array( '+0000000000001999-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'+1999999' =>
				array( '+0000000001999999-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'-1999' =>
				array( '-0000000000001999-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'-1999999' =>
				array( '-0000000001999999-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'100BC' =>
				array( '-0000000000000100-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100a , TimeFormatter::CALENDAR_GREGORIAN ),
			'100 BC' =>
				array( '-0000000000000100-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100a , TimeFormatter::CALENDAR_GREGORIAN ),
			'101 BC' =>
				array( '-0000000000000101-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'100BCE' =>
				array( '-0000000000000100-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100a , TimeFormatter::CALENDAR_GREGORIAN ),
			'100 BCE' =>
				array( '-0000000000000100-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100a , TimeFormatter::CALENDAR_GREGORIAN ),
			'101 BCE' =>
				array( '-0000000000000101-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'101 bce' =>
				array( '-0000000000000101-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'101 bc' =>
				array( '-0000000000000101-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'101 BCe' =>
				array( '-0000000000000101-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'101 before Common Era' =>
				array( '-0000000000000101-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'101 before Christ' =>
				array( '-0000000000000101-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'101before Christ' =>
				array( '-0000000000000101-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'102AD' =>
				array( '+0000000000000102-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'102CE' =>
				array( '+0000000000000102-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'102 CE' =>
				array( '+0000000000000102-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'102 C.E' =>
				array( '+0000000000000102-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'102 A.D' =>
				array( '+0000000000000102-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'102 Anno Domini' =>
				array( '+0000000000000102-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			'102 Common Era' =>
				array( '+0000000000000102-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
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

			//Not within the scope of this parser
			'1 July 20000',

			//We should not try to parse these, this just gets confusing
			'-100BC',
			'+100BC',
			'-100 BC',
			'+100 BC',
			'+100 BCE',
			'+100BCE',
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}