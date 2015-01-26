<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
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
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return MWTimeIsoParser
	 */
	protected function getInstance() {
		return new MWTimeIsoParser();
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 */
	public function validInputProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';

		$argLists = array();

		$valid = array(
			// + dates
			'13 billion years CE' =>
				array( '+0000013000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'130 billion years CE' =>
				array( '+0000130000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'13000 billion years CE' =>
				array( '+0013000000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'13,000 billion years CE' =>
				array( '+0013000000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'13,000 million years CE' =>
				array( '+0000013000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'13,800 million years CE' =>
				array( '+0000013800000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100Ma, $gregorian ),
			'100 million years CE' =>
				array( '+0000000100000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100Ma, $gregorian ),
			'70 million years CE' =>
				array( '+0000000070000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10Ma, $gregorian ),
			'77 million years CE' =>
				array( '+0000000077000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ma, $gregorian ),
			'13 million years CE' =>
				array( '+0000000013000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ma, $gregorian ),
			'1 million years CE' =>
				array( '+0000000001000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ma, $gregorian ),
			'100000 years CE' =>
				array( '+0000000000100000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100ka, $gregorian ),
			'100,000 years CE' =>
				array( '+0000000000100000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100ka, $gregorian ),
			'10000 years CE' =>
				array( '+0000000000010000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10ka, $gregorian ),
			'99000 years CE' =>
				array( '+0000000000099000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'99,000 years CE' =>
				array( '+0000000000099000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'5. millennium' =>
				array( '+0000000000005000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'55. millennium' =>
				array( '+0000000000055000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'10. century' =>
				array( '+0000000000001000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100a, $gregorian ),
			'12. century' =>
				array( '+0000000000001200-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100a, $gregorian ),
			'1980s' =>
				array( '+0000000000001980-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),
			'2000s' =>
				array( '+0000000000002000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),
			'10s' =>
				array( '+0000000000000010-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),
			'12s' =>
				array( '+0000000000000012-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),

			// - dates
			'13 billion years BCE' =>
				array( '-0000013000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'130 billion years BCE' =>
				array( '-0000130000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'13000 billion years BCE' =>
				array( '-0013000000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'13,000 billion years BCE' =>
				array( '-0013000000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'13,000 million years BCE' =>
				array( '-0000013000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
			'13,800 million years BCE' =>
				array( '-0000013800000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100Ma, $gregorian ),
			'100 million years BCE' =>
				array( '-0000000100000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100Ma, $gregorian ),
			'70 million years BCE' =>
				array( '-0000000070000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10Ma, $gregorian ),
			'77 million years BCE' =>
				array( '-0000000077000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ma, $gregorian ),
			'13 million years BCE' =>
				array( '-0000000013000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ma, $gregorian ),
			'1 million years BCE' =>
				array( '-0000000001000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ma, $gregorian ),
			'100000 years BCE' =>
				array( '-0000000000100000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100ka, $gregorian ),
			'100,000 years BCE' =>
				array( '-0000000000100000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100ka, $gregorian ),
			'10000 years BCE' =>
				array( '-0000000000010000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10ka, $gregorian ),
			'99000 years BCE' =>
				array( '-0000000000099000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'99,000 years BCE' =>
				array( '-0000000000099000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'5. millennium BCE' =>
				array( '-0000000000005000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'55. millennium BCE' =>
				array( '-0000000000055000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'10. century BCE' =>
				array( '-0000000000001000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100a, $gregorian ),
			'12. century BCE' =>
				array( '-0000000000001200-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100a, $gregorian ),
			'10s BCE' =>
				array( '-0000000000000010-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),
			'12s BCE' =>
				array( '-0000000000000012-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),
			// also parse BC
			'5. millennium BC' =>
				array( '-0000000000005000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'55. millennium BC' =>
				array( '-0000000000055000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_ka, $gregorian ),
			'10. century BC' =>
				array( '-0000000000001000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100a, $gregorian ),
			'12. century BC' =>
				array( '-0000000000001200-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_100a, $gregorian ),
			'10s BC' =>
				array( '-0000000000000010-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),
			'12s BC' =>
				array( '-0000000000000012-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),
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

			//Not within the scope of this parser
			'200000000',
			'1 June 2013',
			'June 2013',
			'2000',
			'1980x',
			'1980ss',
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}
