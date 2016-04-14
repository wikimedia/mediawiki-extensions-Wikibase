<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueParsers\IsoTimestampParser;
use ValueParsers\MonthNameProvider;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Lib\MwTimeIsoFormatter;
use Wikibase\Repo\Parsers\TimeParserFactory;

/**
 * @covers Wikibase\Lib\MwTimeIsoFormatter
 * @covers Wikibase\Repo\Parsers\TimeParserFactory
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class TimeFormatterParserRoundtripTest extends PHPUnit_Framework_TestCase {

	private function newTimeParserFactory( ParserOptions $options = null ) {
		$monthNameProvider = $this->getMock( MonthNameProvider::class );
		$monthNameProvider->expects( $this->any() )
			->method( 'getLocalizedMonthNames' )
			->will( $this->returnValue( array(
				1 => 'January',
				8 => 'August',
				12 => 'December',
			) ) );
		$monthNameProvider->expects( $this->any() )
			->method( 'getMonthNumbers' )
			->will( $this->returnValue( array(
				'January' => 1,
				'8月' => 8,
				'agosto' => 8,
				'Augusti' => 8,
				'Avgust' => 8,
				'December' => 12,
			) ) );

		return new TimeParserFactory( $options, $monthNameProvider );
	}

	public function isoTimestampProvider() {
		return array(
			// Going up the precision chain
			array( '+0000001987654321-12-31T00:00:00Z', TimeValue::PRECISION_DAY ),
			array( '+0000001987654321-12-00T00:00:00Z', TimeValue::PRECISION_MONTH ),
			array( '+0000001987654321-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			array( '+0000001987654320-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10 ),
			array( '+0000001987654300-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100 ),
			array( '+0000001987654000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K ),
			array( '+0000001987650000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10K ),
			array( '+0000001987600000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100K ),
			array( '+0000001987000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M ),
			array( '+0000001980000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10M ),
			array( '+0000001900000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100M ),
			array( '+0000001000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ),
		);
	}

	public function timeValueProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$cases = array();

		foreach ( $this->isoTimestampProvider() as $case ) {
			$cases[] = array(
				new TimeValue( $case[0], 0, 0, 0, $case[1], $gregorian )
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider timeValueProvider
	 */
	public function testFormatterParserRoundtrip( TimeValue $expected ) {
		$formatter = new MwTimeIsoFormatter();
		$factory = $this->newTimeParserFactory();
		$parser = $factory->getTimeParser();

		$formatted = $formatter->format( $expected );
		/** @var TimeValue $timeValue */
		$timeValue = $parser->parse( $formatted );

		// Yes, this is a duplicate test for the sake of readability if it fails
		$this->assertSame( $expected->getTime(), $timeValue->getTime() );
		$this->assertTrue( $expected->equals( $timeValue ) );
	}

	public function formattedTimeProvider() {
		return array(
			// Basic day, month and year formats that currently do not have a message
			array( '31 January 1987654321' ),
			array( 'January 1987654321' ),
			array( '1987654321' ),

			// All the message based formats
			array( '1 billion years CE' ), //wikibase-time-precision-Gannum
			array( '1 million years CE' ), //wikibase-time-precision-Mannum
			array( '10000 years CE' ), //wikibase-time-precision-annum
			array( '1. millennium' ), //wikibase-time-precision-millennium
			array( '1. century' ), //wikibase-time-precision-century
			array( '10s' ), //wikibase-time-precision-10annum
			array( '1 billion years BCE' ), //wikibase-time-precision-BCE-Gannum
			array( '1 million years BCE' ), //wikibase-time-precision-BCE-Mannum
			array( '10000 years BCE' ), //wikibase-time-precision-BCE-annum
			array( '1. millennium BCE' ), //wikibase-time-precision-BCE-millennium
			array( '1. century BCE' ), //wikibase-time-precision-BCE-century
			array( '10s BCE' ), //wikibase-time-precision-BCE-10annum
		);
	}

	/**
	 * @dataProvider formattedTimeProvider
	 */
	public function testParserFormatterRoundtrip( $expected ) {
		$factory = $this->newTimeParserFactory();
		$parser = $factory->getTimeParser();
		$formatter = new MwTimeIsoFormatter();

		/** @var TimeValue $timeValue */
		$timeValue = $parser->parse( $expected );
		$formatted = $formatter->format( $timeValue );

		$this->assertSame( $expected, $formatted );
	}

	/**
	 * @dataProvider precisionDayProvider
	 */
	public function testPrecisionDayRoundtrip( TimeValue $timeValue, $formatted, $languageCode ) {
		$formatterOptions = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $languageCode,
		) );
		$formatter = new MwTimeIsoFormatter( $formatterOptions );

		$parserOptions = new ParserOptions( array(
			ValueParser::OPT_LANG => $languageCode,
			IsoTimestampParser::OPT_PRECISION => $timeValue->getPrecision(),
			IsoTimestampParser::OPT_CALENDAR => $timeValue->getCalendarModel(),
		) );
		$factory = $this->newTimeParserFactory( $parserOptions );
		$parser = $factory->getTimeParser();

		$this->assertSame( $formatted, $formatter->format( $timeValue ) );
		$this->assertEquals( $timeValue, $parser->parse( $formatted ) );
	}

	public function precisionDayProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$cases = array();

		$tests = array(
			// Positive dates
			array(
				'+2013-08-16T00:00:00Z',
				'16 August 2013',
			),
			array(
				'+00000002013-07-16T00:00:00Z',
				'16 July 2013',
			),
			array(
				'+00000000001-01-14T00:00:00Z',
				'14 January 1',
			),
			array(
				'+00000010000-01-01T00:00:00Z',
				'1 January 10000',
			),

			// Negative dates
			array(
				'-2013-08-16T00:00:00Z',
				'16 August 2013 BCE',
			),
			array(
				'-00000002013-07-16T00:00:00Z',
				'16 July 2013 BCE',
			),
			array(
				'-00000000001-01-14T00:00:00Z',
				'14 January 1 BCE',
			),
			array(
				'-00000010000-01-01T00:00:00Z',
				'1 January 10000 BCE',
			),

			// Some languages default to genitive month names
			array(
				'+2013-08-16T00:00:00Z',
				// Nominative is "Augustus", genitive is "Augusti".
				'16 Augusti 2013',
				'la'
			),

			// Preserve punctuation as given in MessagesXx.php but skip suffixes and words
			array(
				'+2013-08-16T00:00:00Z',
				'16 Avgust, 2013',
				'kaa'
			),
			array(
				'+2013-08-16T00:00:00Z',
				'16 agosto 2013',
				'pt'
			),
			array(
				'+2013-08-16T00:00:00Z',
				'16 8月 2013',
				'yue'
			),
		);

		foreach ( $tests as $args ) {
			$timestamp = $args[0];
			$formatted = $args[1];
			$languageCode = isset( $args[2] ) ? $args[2] : 'en';

			$cases[] = array(
				new TimeValue( $timestamp, 0, 0, 0, TimeValue::PRECISION_DAY, $gregorian ),
				$formatted,
				$languageCode
			);
		}

		return $cases;
	}

}
