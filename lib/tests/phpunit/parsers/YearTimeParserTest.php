<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\EraParser;
use Wikibase\Lib\Parsers\YearTimeParser;

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
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return YearTimeParser
	 */
	protected function getInstance() {
		return new YearTimeParser( $this->getMockEraParser() );
	}

	private function getMockEraParser() {
		$mock = $this->getMockBuilder( 'Wikibase\Lib\Parsers\EraParser' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'parse' )
			->with( $this->isType( 'string' ) )
			->will( $this->returnCallback(
				function( $value ) {
					$sign = EraParser::CURRENT_ERA;
					// Tiny parser that supports a single negative sign only
					if ( $value[0] === EraParser::BEFORE_CURRENT_ERA ) {
						$sign = EraParser::BEFORE_CURRENT_ERA;
						$value = substr( $value, 1 );
					}
					return array( $sign, $value ) ;
				}
			) );
		return $mock;
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';

		$argLists = array();

		$valid = array(
			'1999' =>
				array( '+0000000000001999-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
			'2000' =>
				array( '+0000000000002000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
			'2010' =>
				array( '+0000000000002010-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
			'2000000' =>
				array( '+0000000002000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR1M, $gregorian ),
			'2000000000' =>
				array( '+0000002000000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR1G, $gregorian ),
			'2000020000' =>
				array( '+0000002000020000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR10K, $gregorian ),
			'2000001' =>
				array( '+0000000002000001-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
			'02000001' =>
				array( '+0000000002000001-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
			'1' =>
				array( '+0000000000000001-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
			'000000001' =>
				array( '+0000000000000001-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
			'-1000000' =>
				array( '-0000000001000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR1M, $gregorian ),
			'-1 000 000' =>
				array( '-0000000001000000-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR1M, $gregorian ),
			// Digit grouping in the Indian numbering system
			'-1,99,999' =>
				array( '-0000000000199999-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
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

			// Invalid thousands separator
			'-1/000/000',

			// Positive years are unlikely to have thousands separators, it's more likely a date
			'1 000 000',
			'1,99,999',
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

	/**
	 * @expectedException \ValueParsers\ParseException
	 * @expectedExceptionMessage Failed to parse year
	 */
	public function testParseExceptionMessage() {
		$parser = $this->getInstance();
		$parser->parse( 'ju5t 1nval1d' );
	}

}
