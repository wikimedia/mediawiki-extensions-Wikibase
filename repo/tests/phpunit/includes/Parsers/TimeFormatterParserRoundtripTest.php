<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use MediaWiki\MediaWikiServices;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueParsers\IsoTimestampParser;
use ValueParsers\MonthNameProvider;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Lib\Formatters\MwTimeIsoFormatter;
use Wikibase\Repo\Parsers\TimeParserFactory;

/**
 * @covers \Wikibase\Lib\Formatters\MwTimeIsoFormatter
 * @covers \Wikibase\Repo\Parsers\TimeParserFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TimeFormatterParserRoundtripTest extends \PHPUnit\Framework\TestCase {

	private function newTimeParserFactory( ParserOptions $options = null ) {
		$monthNameProvider = $this->createMock( MonthNameProvider::class );
		$monthNameProvider->method( 'getLocalizedMonthNames' )
			->willReturn( [
				1 => 'January',
				8 => 'August',
				12 => 'December',
			] );
		$monthNameProvider->method( 'getMonthNumbers' )
			->willReturn( [
				'January' => 1,
				'agosto' => 8,
				'Augusti' => 8,
				'Avgust' => 8,
				'December' => 12,
			] );

		return new TimeParserFactory( $options, $monthNameProvider );
	}

	public function isoTimestampProvider() {
		return [
			// Going up the precision chain
			[ '+0000001987654321-12-31T00:00:00Z', TimeValue::PRECISION_DAY ],
			[ '+0000001987654321-12-00T00:00:00Z', TimeValue::PRECISION_MONTH ],
			[ '+0000001987654321-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ],
			[ '+0000001987654320-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10 ],
			[ '+0000001987654300-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100 ],
			[ '+0000001987654000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K ],
			[ '+0000001987650000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10K ],
			[ '+0000001987600000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100K ],
			[ '+0000001987000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M ],
			[ '+0000001980000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10M ],
			[ '+0000001900000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100M ],
			[ '+0000001000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ],
		];
	}

	public function timeValueProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';

		foreach ( $this->isoTimestampProvider() as $case ) {
			yield [ new TimeValue( $case[0], 0, 0, 0, $case[1], $gregorian ) ];
		}
	}

	/**
	 * @dataProvider timeValueProvider
	 */
	public function testFormatterParserRoundtrip( TimeValue $expected ) {
		$formatter = new MwTimeIsoFormatter( MediaWikiServices::getInstance()->getLanguageFactory() );
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
		return [
			// Basic day, month and year formats that currently do not have a message
			[ '31 January 1987654321' ],
			[ 'January 1987654321' ],
			[ '1987654321' ],

			// All the message based formats
			[ '1 billion years CE' ], //wikibase-time-precision-Gannum
			[ '1 million years CE' ], //wikibase-time-precision-Mannum
			[ '10000 years CE' ], //wikibase-time-precision-annum
			[ '1. millennium' ], //wikibase-time-precision-millennium
			[ '1. century' ], //wikibase-time-precision-century
			[ '10s' ], //wikibase-time-precision-10annum
			[ '10 CE' ], //wikibase-time-precision-CE
			[ '1 billion years BCE' ], //wikibase-time-precision-BCE-Gannum
			[ '1 million years BCE' ], //wikibase-time-precision-BCE-Mannum
			[ '10000 years BCE' ], //wikibase-time-precision-BCE-annum
			[ '1. millennium BCE' ], //wikibase-time-precision-BCE-millennium
			[ '1. century BCE' ], //wikibase-time-precision-BCE-century
			[ '10s BCE' ], //wikibase-time-precision-BCE-10annum
			[ '10 BCE' ], //wikibase-time-precision-BCE
		];
	}

	/**
	 * @dataProvider formattedTimeProvider
	 */
	public function testParserFormatterRoundtrip( $expected ) {
		$factory = $this->newTimeParserFactory();
		$parser = $factory->getTimeParser();
		$formatter = new MwTimeIsoFormatter( MediaWikiServices::getInstance()->getLanguageFactory() );

		/** @var TimeValue $timeValue */
		$timeValue = $parser->parse( $expected );
		$formatted = $formatter->format( $timeValue );

		$this->assertSame( $expected, $formatted );
	}

	/**
	 * @dataProvider precisionDayProvider
	 */
	public function testPrecisionDayRoundtrip( TimeValue $timeValue, $formatted, $languageCode ) {
		$formatterOptions = new FormatterOptions( [
			ValueFormatter::OPT_LANG => $languageCode,
		] );
		$formatter = new MwTimeIsoFormatter( MediaWikiServices::getInstance()->getLanguageFactory(), $formatterOptions );

		$parserOptions = new ParserOptions( [
			ValueParser::OPT_LANG => $languageCode,
			IsoTimestampParser::OPT_PRECISION => $timeValue->getPrecision(),
			IsoTimestampParser::OPT_CALENDAR => $timeValue->getCalendarModel(),
		] );
		$factory = $this->newTimeParserFactory( $parserOptions );
		$parser = $factory->getTimeParser();

		$this->assertSame( $formatted, $formatter->format( $timeValue ) );
		$this->assertEquals( $timeValue, $parser->parse( $formatted ) );
	}

	public function precisionDayProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';

		$tests = [
			// Positive dates
			[
				'+2013-08-16T00:00:00Z',
				'16 August 2013',
			],
			[
				'+00000002013-07-16T00:00:00Z',
				'16 July 2013',
			],
			[
				'+00000000001-01-14T00:00:00Z',
				'14 January 1 CE',
			],
			[
				'+00000010000-01-01T00:00:00Z',
				'1 January 10000',
			],

			// Negative dates
			[
				'-2013-08-16T00:00:00Z',
				'16 August 2013 BCE',
			],
			[
				'-00000002013-07-16T00:00:00Z',
				'16 July 2013 BCE',
			],
			[
				'-00000000001-01-14T00:00:00Z',
				'14 January 1 BCE',
			],
			[
				'-00000010000-01-01T00:00:00Z',
				'1 January 10000 BCE',
			],

			// Some languages default to genitive month names
			[
				'+2013-08-16T00:00:00Z',
				// Nominative is "Augustus", genitive is "Augusti".
				'16 Augusti 2013',
				'la',
			],

			// Preserve punctuation as given in MessagesXx.php but skip suffixes and words
			[
				'+2013-08-16T00:00:00Z',
				'16 Avgust, 2013',
				'kaa',
			],
			[
				'+2013-08-16T00:00:00Z',
				'16 agosto 2013',
				'pt',
			],
			[
				'+2013-08-16T00:00:00Z',
				'16 8 2013',
				'yue',
			],
		];

		foreach ( $tests as $args ) {
			$timestamp = $args[0];
			$formatted = $args[1];
			$languageCode = $args[2] ?? 'en';

			yield [
				new TimeValue( $timestamp, 0, 0, 0, TimeValue::PRECISION_DAY, $gregorian ),
				$formatted,
				$languageCode,
			];
		}
	}

}
