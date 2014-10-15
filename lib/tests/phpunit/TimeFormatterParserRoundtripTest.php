<?php

namespace Wikibase\Lib\Test;

use DataValues\TimeValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueParsers\ParserOptions;
use Wikibase\Lib\MwTimeIsoFormatter;
use Wikibase\Lib\Parsers\TimeParser;

/**
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class TimeFormatterParserRoundtripTest extends \MediaWikiTestCase {

	public function isoTimestampProvider() {
		return array(
			// Going up the precision chain
			array( '+0000001987654321-12-31T00:00:00Z', TimeValue::PRECISION_DAY ),
			array( '+0000001987654321-12-00T00:00:00Z', TimeValue::PRECISION_MONTH ),
			array( '+0000001987654321-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			array( '+0000001987654320-00-00T00:00:00Z', TimeValue::PRECISION_10a ),
			array( '+0000001987654300-00-00T00:00:00Z', TimeValue::PRECISION_100a ),
			array( '+0000001987654000-00-00T00:00:00Z', TimeValue::PRECISION_ka ),
			array( '+0000001987650000-00-00T00:00:00Z', TimeValue::PRECISION_10ka ),
			array( '+0000001987600000-00-00T00:00:00Z', TimeValue::PRECISION_100ka ),
			array( '+0000001987000000-00-00T00:00:00Z', TimeValue::PRECISION_Ma ),
			array( '+0000001980000000-00-00T00:00:00Z', TimeValue::PRECISION_10Ma ),
			array( '+0000001900000000-00-00T00:00:00Z', TimeValue::PRECISION_100Ma ),
			array( '+0000001000000000-00-00T00:00:00Z', TimeValue::PRECISION_Ga ),
		);
	}

	public function timeValueProvider() {
		$cases = array();

		foreach ( $this->isoTimestampProvider() as $case ) {
			$cases[] = array(
				new TimeValue( $case[0], 0, 0, 0, $case[1], TimeFormatter::CALENDAR_GREGORIAN )
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider timeValueProvider
	 * @param TimeValue $expected
	 */
	public function testFormatterParserRoundtrip( TimeValue $expected ) {
		$formatter = new MwTimeIsoFormatter( new FormatterOptions() );
		$parser = new TimeParser();

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
	 * @param string $expected
	 */
	public function testParserFormatterRoundtrip( $expected ) {
		$parser = new TimeParser();
		$formatter = new MwTimeIsoFormatter( new FormatterOptions() );

		/** @var TimeValue $timeValue */
		$timeValue = $parser->parse( $expected );
		$formatted = $formatter->format( $timeValue );

		$this->assertSame( $expected, $formatted );
	}

}
