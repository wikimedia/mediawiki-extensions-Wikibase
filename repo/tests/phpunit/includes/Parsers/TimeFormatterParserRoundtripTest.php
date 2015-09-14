<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueParsers\IsoTimestampParser;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Lib\MwTimeIsoFormatter;
use Wikibase\Lib\Parsers\TimeParserFactory;

/**
 * @covers Wikibase\Lib\MwTimeIsoFormatter
 * @covers Wikibase\Lib\Parsers\TimeParserFactory
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class TimeFormatterParserRoundtripTest extends PHPUnit_Framework_TestCase {

	public function formatProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';

		$tests = array(
			// Positive dates
			array(
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 August 2013',
			),
			array(
				'+00000002013-07-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 July 2013',
			),
			array(
				'+00000000001-01-14T00:00:00Z', TimeValue::PRECISION_DAY,
				'14 January 1',
			),
			array(
				'+00000010000-01-01T00:00:00Z', TimeValue::PRECISION_DAY,
				'1 January 10000',
			),

			// Negative dates
			array(
				'-2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 August 2013 BCE',
			),
			array(
				'-00000002013-07-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 July 2013 BCE',
			),
			array(
				'-00000000001-01-14T00:00:00Z', TimeValue::PRECISION_DAY,
				'14 January 1 BCE',
			),
			array(
				'-00000010000-01-01T00:00:00Z', TimeValue::PRECISION_DAY,
				'1 January 10000 BCE',
			),

			// Some languages default to genitive month names
			array(
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				// Nominative is "Augustus", genitive is "Augusti".
				'16 Augusti 2013',
				'la'
			),

			// Preserve punctuation as given in MessagesXx.php but skip suffixes and words
			array(
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 Avgust, 2013',
				'kaa'
			),
			array(
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 agosto 2013',
				'pt'
			),
			array(
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 8月 2013',
				'yue'
			),
		);

		$argLists = array();

		foreach ( $tests as $args ) {
			$timestamp = $args[0];
			$precision = $args[1];
			$formatted = $args[2];
			$languageCode = isset( $args[3] ) ? $args[3] : 'en';

			$argLists[] = array(
				new TimeValue( $timestamp, 0, 0, 0, $precision, $gregorian ),
				$formatted,
				$languageCode
			);
		}

		return $argLists;
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormatterParserRoundtrip( TimeValue $timeValue, $formatted, $languageCode ) {
		$formatterOptions = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $languageCode,
		) );
		$formatter = new MwTimeIsoFormatter( $formatterOptions );

		$parserOptions = new ParserOptions( array(
			ValueParser::OPT_LANG => $languageCode,
			IsoTimestampParser::OPT_PRECISION => $timeValue->getPrecision(),
			IsoTimestampParser::OPT_CALENDAR => $timeValue->getCalendarModel(),
		) );
		$factory = new TimeParserFactory( $parserOptions );
		$parser = $factory->getTimeParser();

		$this->assertSame( $formatted, $formatter->format( $timeValue ) );
		$this->assertEquals( $timeValue, $parser->parse( $formatted ) );
	}

}
