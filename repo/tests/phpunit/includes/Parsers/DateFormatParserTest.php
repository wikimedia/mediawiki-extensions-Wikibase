<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\DataValue;
use DataValues\TimeValue;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\Parsers\DateFormatParser;

/**
 * @covers \Wikibase\Repo\Parsers\DateFormatParser
 *
 * @group ValueParsers
 * @group WikibaseRepo
 * @group Wikibase
 * @group TimeParsers
 *
 * @license GPL-2.0-or-later
 */
class DateFormatParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return DateFormatParser
	 */
	protected function getInstance() {
		return new DateFormatParser();
	}

	/**
	 * @inheritDoc
	 */
	public function validInputProvider() {
		$monthNames = [ 9 => [ 'Sep', 'September' ] ];

		$valid = [
			'Default options' => [
				'1 9 2014',
				'd M Y', null, null,
				'+2014-09-01T00:00:00Z',
			],
			'Date format with slashes' => [
				'9/11/1876',
				'j/F/Y', null, null,
				'+1876-11-09T00:00:00Z',
			],
			'Transform map' => [
				'Z g Zo15',
				'd M Y', [ '0' => 'o', 2 => 'Z', 9 => 'g' ], null,
				'+2015-09-02T00:00:00Z',
			],
			'Default month map' => [
				'1. September 2014',
				'd. M Y', null, $monthNames,
				'+2014-09-01T00:00:00Z',
			],
			'Simple month map' => [
				'1 September 2014',
				'd M Y', null, [ 9 => 'September' ],
				'+2014-09-01T00:00:00Z',
			],
			'Genitive month name' => [
				'1 September 2014',
				'd xg Y', null, $monthNames,
				'+2014-09-01T00:00:00Z',
			],
			'Month before day' => [
				'junho 12 1990',
				'F j Y', null, [ 6 => 'junho' ],
				'+1990-06-12T00:00:00Z',
			],
			'Escapes' => [
				'1s 9s 2014\\',
				'd\\s M\\s Y\\', null, null,
				'+2014-09-01T00:00:00Z',
			],
			'Quotes' => [
				'1th 9th 2014"',
				'd"th" M"th" Y"', null, null,
				'+2014-09-01T00:00:00Z',
			],
			'Portuguese date format with quotes' => [
				'9 de novembro de 1876',
				'j "de" F "de" Y', null, [ 11 => 'novembro' ],
				'+1876-11-09T00:00:00Z',
			],
			'Raw characters' => [
				'1 9ä 2014',
				'd Mä Y', null, null,
				'+2014-09-01T00:00:00Z',
			],
			'xx escaping' => [
				'2014 x9 1',
				'Y xxm d', null, null,
				'+2014-09-01T00:00:00Z',
			],
			'Raw modifiers' => [
				'2014 9 1',
				'Y xNmxN xnd', null, null,
				'+2014-09-01T00:00:00Z',
			],
			'Whitespace is optional' => [
				'1September2014',
				'd M Y', null, $monthNames,
				'+2014-09-01T00:00:00Z',
			],
			'Delimiters are optional' => [
				'1 9 2014',
				'd. M. Y', null, null,
				'+2014-09-01T00:00:00Z',
			],
			'Delimiters are ignored' => [
				'1. 9. 2014',
				'd M Y', null, null,
				'+2014-09-01T00:00:00Z',
			],
			'Year precision' => [
				'2014',
				'Y', null, null,
				'+2014-00-00T00:00:00Z', TimeValue::PRECISION_YEAR,
			],
			'Month precision' => [
				'9 2014',
				'M Y', null, null,
				'+2014-09-00T00:00:00Z', TimeValue::PRECISION_MONTH,
			],
			'Minute precision' => [
				'1 9 2014 15:30',
				'd M Y H:i', null, null,
				'+2014-09-01T15:30:00Z', TimeValue::PRECISION_MINUTE,
			],
			'Second precision' => [
				'1 9 2014 15:30:59',
				'd M Y H:i:s', null, null,
				'+2014-09-01T15:30:59Z', TimeValue::PRECISION_SECOND,
			],
		];

		$cases = [];

		foreach ( $valid as $key => $args ) {
			$dateString = $args[0];
			$dateFormat = $args[1];
			$digitTransformTable = $args[2];
			$monthNames = $args[3];
			$timestamp = $args[4];
			$precision = $args[5] ?? TimeValue::PRECISION_DAY;
			$calendarModel = $args[6] ?? TimeValue::CALENDAR_GREGORIAN;

			$cases[$key] = [
				$dateString,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel ),
				new DateFormatParser( new ParserOptions( [
					DateFormatParser::OPT_DATE_FORMAT => $dateFormat,
					DateFormatParser::OPT_DIGIT_TRANSFORM_TABLE => $digitTransformTable,
					DateFormatParser::OPT_MONTH_NAMES => $monthNames,
				] ) ),
			];
		}

		return $cases;
	}

	/**
	 * @inheritDoc
	 */
	public function invalidInputProvider() {
		$invalid = [
			'',
			'1',
			'20. Jahrhundert',
			'1 2',
			'-1 2 3',
			'1 -2 3',
			'1 2 -3',
			'1st 2nd 3',
			'1 13 1',
			'32 1 1',

			// With the correct options these can be valid, but not with the default ones.
			'9/11/1876',
			'junho 12 1990',
			'9 de novembro de 1876',
		];

		foreach ( $invalid as $value ) {
			yield [ $value ];
		}
	}

	/**
	 * @dataProvider unsupportedDateFormatProvider
	 */
	public function testUnsupportedDateFormatOption( $format ) {
		$parser = new DateFormatParser( new ParserOptions( [
			DateFormatParser::OPT_DATE_FORMAT => $format,
		] ) );
		$this->expectException( ParseException::class );
		$this->expectExceptionMessage( 'Unsupported date format' );
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
			[ 'xiF' ],
			[ 'xij' ],
			[ 'xin' ],
			[ 'xit' ],
			[ 'xiY' ],
			[ 'xiy' ],
			[ 'xiz' ],
			[ 'xjF' ],
			[ 'xjj' ],
			[ 'xjn' ],
			[ 'xjt' ],
			[ 'xjx' ],
			[ 'xjY' ],
			[ 'xkY' ],
			[ 'xmF' ],
			[ 'xmj' ],
			[ 'xmn' ],
			[ 'xmY' ],
			[ 'xoY' ],
			[ 'xr' ],
			[ 'xtY' ],
			[ 'y' ],
			[ 'Z' ],
			[ 'z' ],
		];
	}

	public function testInvalidInputException() {
		$parser = new DateFormatParser();
		$this->expectException( ParseException::class );
		$this->expectExceptionMessage( 'Failed to parse' );
		$parser->parse( '' );
	}

	public function testIllegalDateFormatOption() {
		$parser = new DateFormatParser( new ParserOptions( [
			DateFormatParser::OPT_DATE_FORMAT => 'Y Y',
		] ) );
		$this->expectException( ParseException::class );
		$this->expectExceptionMessage( 'Illegal date format' );
		$parser->parse( '' );
	}

	/**
	 * @dataProvider nonContinuousDateFormatProvider
	 */
	public function testNonContinuousDateFormat( $input, $format ) {
		$parser = new DateFormatParser( new ParserOptions( [
			DateFormatParser::OPT_DATE_FORMAT => $format,
			DateFormatParser::OPT_MONTH_NAMES => [ 6 => 'juin' ],
		] ) );
		$this->expectException( ParseException::class );
		$this->expectExceptionMessage( 'Non-continuous date format' );
		$parser->parse( $input );
	}

	public function nonContinuousDateFormatProvider() {
		return [
			'Day' => [ '1', 'j' ],
			'Month' => [ '9', 'F' ],
			'Day month' => [ '1 9', 'j F' ],
			'Named month without year' => [ '1er juin', 'j"er" F' ],

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

	public function testSetAndGetOptions() {
		$parser = $this->getInstance();

		$parser->setOptions( new ParserOptions() );

		$this->assertEquals( new ParserOptions(), $parser->getOptions() );

		$options = new ParserOptions();
		$options->setOption( 'someoption', 'someoption' );

		$parser->setOptions( $options );

		$this->assertEquals( $options, $parser->getOptions() );
	}

	/**
	 * @since 0.1
	 *
	 * @dataProvider validInputProvider
	 * @param mixed $value
	 * @param mixed $expected
	 * @param ValueParser|null $parser
	 */
	public function testParseWithValidInputs( $value, $expected, ValueParser $parser = null ) {
		if ( $parser === null ) {
			$parser = $this->getInstance();
		}

		$this->assertSmartEquals( $expected, $parser->parse( $value ) );
	}

	/**
	 * @param DataValue|mixed $expected
	 * @param DataValue|mixed $actual
	 */
	private function assertSmartEquals( $expected, $actual ) {
		if ( $this->requireDataValue() ) {
			if ( $expected instanceof DataValue && $actual instanceof DataValue ) {
				$msg = "testing equals():\n"
					. preg_replace( '/\s+/', ' ', print_r( $actual->toArray(), true ) ) . " should equal\n"
					. preg_replace( '/\s+/', ' ', print_r( $expected->toArray(), true ) );
			} else {
				$msg = 'testing equals()';
			}

			$this->assertTrue( $expected->equals( $actual ), $msg );
		} else {
			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 * @since 0.1
	 *
	 * @dataProvider invalidInputProvider
	 * @param mixed $value
	 * @param ValueParser|null $parser
	 */
	public function testParseWithInvalidInputs( $value, ValueParser $parser = null ) {
		if ( $parser === null ) {
			$parser = $this->getInstance();
		}

		$this->expectException( 'ValueParsers\ParseException' );
		$parser->parse( $value );
	}

	/**
	 * Returns if the result of the parsing process should be checked to be a DataValue.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	protected function requireDataValue() {
		return true;
	}

}
