<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Repo\Parsers\DateFormatParser;

/**
 * @covers Wikibase\Repo\Parsers\DateFormatParser
 *
 * @group ValueParsers
 * @group WikibaseRepo
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DateFormatParserTest extends StringValueParserTest {

	/**
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return DateFormatParser
	 */
	protected function getInstance() {
		return new DateFormatParser();
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 */
	public function validInputProvider() {
		$monthNames = [ 9 => [ 'Sep', 'September' ] ];

		$valid = [
			'Default options' => [
				'1 9 2014',
				'd M Y', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'Transform map' => [
				'Z g Zo15',
				'd M Y', [ '0' => 'o', 2 => 'Z', 9 => 'g' ], null,
				'+2015-09-02T00:00:00Z'
			],
			'Default month map' => [
				'1. September 2014',
				'd. M Y', null, $monthNames,
				'+2014-09-01T00:00:00Z'
			],
			'Simple month map' => [
				'1 September 2014',
				'd M Y', null, [ 9 => 'September' ],
				'+2014-09-01T00:00:00Z'
			],
			'Escapes' => [
				'1s 9s 2014\\',
				'd\\s M\\s Y\\', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'Quotes' => [
				'1th 9th 2014"',
				'd"th" M"th" Y"', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'Raw characters' => [
				'1 9ä 2014',
				'd Mä Y', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'xx escaping' => [
				'2014 x9 1',
				'Y xxm d', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'Raw modifiers' => [
				'2014 9 1',
				'Y xNmxN xnd', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'3-character codes' => [
				'2014 9 1',
				'xjY m d', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'Whitespace is optional' => [
				'1September2014',
				'd M Y', null, $monthNames,
				'+2014-09-01T00:00:00Z'
			],
			'Delimiters are optional' => [
				'1 9 2014',
				'd. M. Y', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'Delimiters are ignored' => [
				'1. 9. 2014',
				'd M Y', null, null,
				'+2014-09-01T00:00:00Z'
			],
			'Year precision' => [
				'2014',
				'Y', null, null,
				'+2014-00-00T00:00:00Z', TimeValue::PRECISION_YEAR
			],
			'Month precision' => [
				'9 2014',
				'M Y', null, null,
				'+2014-09-00T00:00:00Z', TimeValue::PRECISION_MONTH
			],
			'Minute precision' => [
				'1 9 2014 15:30',
				'd M Y H:i', null, null,
				'+2014-09-01T15:30:00Z', TimeValue::PRECISION_MINUTE
			],
			'Second precision' => [
				'1 9 2014 15:30:59',
				'd M Y H:i:s', null, null,
				'+2014-09-01T15:30:59Z', TimeValue::PRECISION_SECOND
			],
		];

		$cases = [];

		foreach ( $valid as $key => $args ) {
			$dateString = $args[0];
			$dateFormat = $args[1];
			$digitTransformTable = $args[2];
			$monthNames = $args[3];
			$timestamp = $args[4];
			$precision = isset( $args[5] ) ? $args[5] : TimeValue::PRECISION_DAY;
			$calendarModel = isset( $args[6] ) ? $args[6] : TimeValue::CALENDAR_GREGORIAN;

			$cases[$key] = [
				$dateString,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel ),
				new DateFormatParser( new ParserOptions( [
					DateFormatParser::OPT_DATE_FORMAT => $dateFormat,
					DateFormatParser::OPT_DIGIT_TRANSFORM_TABLE => $digitTransformTable,
					DateFormatParser::OPT_MONTH_NAMES => $monthNames,
				] ) )
			];
		}

		return $cases;
	}

	/**
	 * @see StringValueParserTest::invalidInputProvider
	 */
	public function invalidInputProvider() {
		$invalid = [
			'',
			'1',
			'1 2',
			'-1 2 3',
			'1 -2 3',
			'1 2 -3',
			'1st 2nd 3',
			'1 13 1',
			'32 1 1',
		];

		$cases = parent::invalidInputProvider();

		foreach ( $invalid as $value ) {
			$cases[] = [ $value ];
		}

		return $cases;
	}

	/**
	 * @dataProvider unsupportedDateFormatProvider
	 */
	public function testUnsupportedDateFormatOption( $format ) {
		$parser = new DateFormatParser( new ParserOptions( [
			DateFormatParser::OPT_DATE_FORMAT => $format,
		] ) );
		$this->setExpectedException( ParseException::class, 'Unsupported date format' );
		$parser->parse( '' );
	}

	public function unsupportedDateFormatProvider() {
		return [
			[ 'A' ],
			[ 'a' ],
			[ 'c' ],
			[ 'D' ],
			[ 'e' ],
			[ 'g' ],
			[ 'h' ],
			[ 'I' ],
			[ 'L' ],
			[ 'l' ],
			[ 'N' ],
			[ 'O' ],
			[ 'P' ],
			[ 'r' ],
			[ 'T' ],
			[ 't' ],
			[ 'U' ],
			[ 'W' ],
			[ 'w' ],
			[ 'xh' ],
			[ 'xit' ],
			[ 'xiy' ],
			[ 'xiz' ],
			[ 'xr' ],
			[ 'y' ],
			[ 'Z' ],
			[ 'z' ],
		];
	}

	public function testInvalidInputException() {
		$parser = new DateFormatParser();
		$this->setExpectedException( ParseException::class, 'Failed to parse' );
		$parser->parse( '' );
	}

	public function testIllegalDateFormatOption() {
		$parser = new DateFormatParser( new ParserOptions( [
			DateFormatParser::OPT_DATE_FORMAT => 'Y Y',
		] ) );
		$this->setExpectedException( ParseException::class, 'Illegal date format' );
		$parser->parse( '' );
	}

	/**
	 * @dataProvider nonContinuousDateFormatProvider
	 */
	public function testNonContinuousDateFormat( $input, $format ) {
		$parser = new DateFormatParser( new ParserOptions( [
			DateFormatParser::OPT_DATE_FORMAT => $format,
		] ) );
		$this->setExpectedException( ParseException::class, 'Non-continuous date format' );
		$parser->parse( $input );
	}

	public function nonContinuousDateFormatProvider() {
		return [
			'Day' => [ '1', 'j' ],
			'Month' => [ '9', 'F' ],
			'Day month' => [ '1 9', 'j F' ],

			'Day year' => [ '1 2014', 'j Y' ],
			'Day year, hour:minute' => [ '1 2014, 12:30', 'j Y, H:i' ],
			'Day year, hour:minute:second' => [ '1 2014, 12:30:00', 'j Y, H:i:s' ],

			'Month year, minute' => [ '9 2014, 30', 'F Y, i' ],
			'Month year, hour:minute' => [ '9 2014, 12:30', 'F Y, H:i' ],
			'Month year, hour:minute:second' => [ '9 2014, 12:30:40', 'F Y, H:i:s' ],

			'Year, hour:minute' => [ '2014, 12:30', 'Y, H:i' ],
			'Year, hour:minute:second' => [ '2014, 12:30:40', 'Y, H:i:s' ],

			'Valid date, minute' => [ '1 9 2014, 30', 'j F Y, i' ],
			'Valid date, second' => [ '1 9 2014, 40', 'j F Y, s' ],
			'Valid date, minute:second' => [ '1 9 2014, 30:40', 'j F Y, i:s' ],
		];
	}

}
