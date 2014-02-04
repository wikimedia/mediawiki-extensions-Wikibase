<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueFormatters\TimeFormatter;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\MWTimeIsoParser;

/**
 * @covers Wikibase\Lib\Parsers\MWTimeIsoParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class MWTimeIsoParserTest extends StringValueParserTest {

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
		return 'Wikibase\Lib\Parsers\MWTimeIsoParser';
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = array();

		$valid = array(

			// + dates
			'in 13 billion years' =>
				array( '+0000013000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 130 billion years' =>
				array( '+0000130000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 13000 billion years' =>
				array( '+0013000000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 13,000 billion years' =>
				array( '+0013000000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 13,000 million years' =>
				array( '+0000013000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 13,800 million years' =>
				array( '+0000013800000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 100 million years' =>
				array( '+0000000100000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 70 million years' =>
				array( '+0000000070000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_10Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 77 million years' =>
				array( '+0000000077000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 13 million years' =>
				array( '+0000000013000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 1 million years' =>
				array( '+0000000001000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 100000 years' =>
				array( '+0000000000100000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 100,000 years' =>
				array( '+0000000000100000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 10000 years' =>
				array( '+0000000000010000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_10ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 99000 years' =>
				array( '+0000000000099000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'in 99,000 years' =>
				array( '+0000000000099000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'5. millennium' =>
				array( '+0000000000005000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'55. millennium' =>
				array( '+0000000000055000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'10. century' =>
				array( '+0000000000001000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'12. century' =>
				array( '+0000000000001200-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100a , TimeFormatter::CALENDAR_GREGORIAN ),
			'10s' =>
				array( '+0000000000000010-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_10a , TimeFormatter::CALENDAR_GREGORIAN ),
			'12s' =>
				array( '+0000000000000012-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),

			// - dates
			'13 billion years ago' =>
				array( '-0000013000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'130 billion years ago' =>
				array( '-0000130000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'13000 billion years ago' =>
				array( '-0013000000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'13,000 billion years ago' =>
				array( '-0013000000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'13,000 million years ago' =>
				array( '-0000013000000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ga , TimeFormatter::CALENDAR_GREGORIAN ),
			'13,800 million years ago' =>
				array( '-0000013800000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'100 million years ago' =>
				array( '-0000000100000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'70 million years ago' =>
				array( '-0000000070000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_10Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'77 million years ago' =>
				array( '-0000000077000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'13 million years ago' =>
				array( '-0000000013000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'1 million years ago' =>
				array( '-0000000001000000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_Ma , TimeFormatter::CALENDAR_GREGORIAN ),
			'100000 years ago' =>
				array( '-0000000000100000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'100,000 years ago' =>
				array( '-0000000000100000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'10000 years ago' =>
				array( '-0000000000010000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_10ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'99000 years ago' =>
				array( '-0000000000099000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'99,000 years ago' =>
				array( '-0000000000099000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'5. millennium BCE' =>
				array( '-0000000000005000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'55. millennium BCE' =>
				array( '-0000000000055000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'10. century BCE' =>
				array( '-0000000000001000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'12. century BCE' =>
				array( '-0000000000001200-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100a , TimeFormatter::CALENDAR_GREGORIAN ),
			'10s BCE' =>
				array( '-0000000000000010-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_10a , TimeFormatter::CALENDAR_GREGORIAN ),
			'12s BCE' =>
				array( '-0000000000000012-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
			// also parse BC
			'5. millennium BC' =>
				array( '-0000000000005000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'55. millennium BC' =>
				array( '-0000000000055000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'10. century BC' =>
				array( '-0000000000001000-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_ka , TimeFormatter::CALENDAR_GREGORIAN ),
			'12. century BC' =>
				array( '-0000000000001200-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_100a , TimeFormatter::CALENDAR_GREGORIAN ),
			'10s BC' =>
				array( '-0000000000000010-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_10a , TimeFormatter::CALENDAR_GREGORIAN ),
			'12s BC' =>
				array( '-0000000000000012-00-00T00:00:00Z', 0 , 0 , 0 , TimeValue::PRECISION_YEAR , TimeFormatter::CALENDAR_GREGORIAN ),
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
			'200000000',
			'1 June 2013',
			'June 2013',
			'2000',
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}