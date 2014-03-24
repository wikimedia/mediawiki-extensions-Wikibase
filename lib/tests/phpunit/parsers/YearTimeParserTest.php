<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use ValueFormatters\TimeFormatter;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Lib\Parsers\EraParser;
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
		return new $class( $this->getMockEraParser(), $this->newParserOptions() );
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
					return array( EraParser::CURRENT_ERA, $value ) ;
				}
			) );
		return $mock;
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

	/**
	 * @expectedException \ValueParsers\ParseException
	 * @expectedExceptionMessage Failed to parse year: just invalid
	 */
	public function testParseExceptionMessage() {
		$this->getInstance()->parse( 'just invalid' );
	}

}