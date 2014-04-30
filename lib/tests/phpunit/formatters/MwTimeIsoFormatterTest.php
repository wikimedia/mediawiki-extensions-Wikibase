<?php

namespace ValueFormatters\Test;

use DataValues\TimeValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Lib\MwTimeIsoFormatter;
use Wikibase\Lib\Parsers\TimeParser;

/**
 * @covers Wikibase\Lib\MwTimeIsoFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class MwTimeIsoFormatterTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
		/*
		 * Temporary wgHooks performance improvement,
		 * this can be removed once the following is merged:
		 * https://gerrit.wikimedia.org/r/#/c/125706/1
		 */
		$this->stashMwGlobals( 'wgHooks' );
	}

	/**
	 * Returns an array of test parameters.
	 *
	 * @return array
	 */
	public function formatProvider() {
		$tests = array(
			// Positive dates
			array(
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 August 2013',
				true
			),
			array(
				'+00000002013-07-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 July 2013',
				true
			),
			array(
				'+00000000001-01-14T00:00:00Z', TimeValue::PRECISION_DAY,
				'14 January 1',
				true
			),
			array(
				'+00000010000-01-01T00:00:00Z', TimeValue::PRECISION_DAY,
				'1 January 10000',
				true
			),
			array(
				'+00000002013-07-16T00:00:00Z', TimeValue::PRECISION_MONTH,
				'July 2013',
			),
			array(
				'+00000002013-07-16T00:00:00Z', TimeValue::PRECISION_YEAR,
				'2013',
			),
			array(
				'+00000000013-07-16T00:00:00Z', TimeValue::PRECISION_YEAR,
				'13',
			),
			array(
				'+00002222013-07-16T00:10:00Z', TimeValue::PRECISION_YEAR,
				'2222013',
			),
			array(
				'+12342222013-07-16T00:10:00Z', TimeValue::PRECISION_YEAR,
				'12342222013',
			),

			// Positive dates, stepping through precisions
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_10a,
				'12345678910s',
			),
			array(
				'+12345678919-01-01T01:01:01Z', TimeValue::PRECISION_10a,
				'12345678920s',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_100a,
				'123456789. century',
			),
			array(
				'+12345678992-01-01T01:01:01Z', TimeValue::PRECISION_100a,
				'123456790. century',
			),
			array(
				'+12345678112-01-01T01:01:01Z', TimeValue::PRECISION_ka,
				'12345678. millennium',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_ka,
				'12345679. millennium',
			),
			array(
				'+12345671912-01-01T01:01:01Z', TimeValue::PRECISION_10ka,
				'in 12345670000 years',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_10ka,
				'in 12345680000 years',
			),
			array(
				'+12345618912-01-01T01:01:01Z', TimeValue::PRECISION_100ka,
				'in 12345600000 years',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_100ka,
				'in 12345700000 years',
			),
			array(
				'+12345178912-01-01T01:01:01Z', TimeValue::PRECISION_Ma,
				'in 12345 million years',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_Ma,
				'in 12346 million years',
			),
			array(
				'+12341678912-01-01T01:01:01Z', TimeValue::PRECISION_10Ma,
				'in 12340 million years',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_10Ma,
				'in 12350 million years',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_100Ma,
				'in 12300 million years',
			),
			array(
				'+12375678912-01-01T01:01:01Z', TimeValue::PRECISION_100Ma,
				'in 12400 million years',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_Ga,
				'in 12 billion years',
			),
			array(
				'+12545678912-01-01T01:01:01Z', TimeValue::PRECISION_Ga,
				'in 13 billion years',
			),

			// Negative dates
			array(
				'-2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 August 2013 BCE',
				true
			),
			array(
				'-00000002013-07-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 July 2013 BCE',
				true
			),
			array(
				'-00000000001-01-14T00:00:00Z', TimeValue::PRECISION_DAY,
				'14 January 1 BCE',
				true
			),
			array(
				'-00000010000-01-01T00:00:00Z', TimeValue::PRECISION_DAY,
				'1 January 10000 BCE',
				true
			),
			array(
				'-00000002013-07-16T00:00:00Z', TimeValue::PRECISION_MONTH,
				'July 2013 BCE',
			),
			array(
				'-00000002013-07-16T00:00:00Z', TimeValue::PRECISION_YEAR,
				'2013 BCE',
			),
			array(
				'-00000000013-07-16T00:00:00Z', TimeValue::PRECISION_YEAR,
				'13 BCE',
			),
			array(
				'-00002222013-07-16T00:10:00Z', TimeValue::PRECISION_YEAR,
				'2222013 BCE',
			),
			array(
				'-12342222013-07-16T00:10:00Z', TimeValue::PRECISION_YEAR,
				'12342222013 BCE',
			),

			// Negative dates, stepping through precisions
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_10a,
				'12345678910s BCE',
			),
			array(
				'-12345678919-01-01T01:01:01Z', TimeValue::PRECISION_10a,
				'12345678920s BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_100a,
				'123456789. century BCE',
			),
			array(
				'-12345678992-01-01T01:01:01Z', TimeValue::PRECISION_100a,
				'123456790. century BCE',
			),
			array(
				'-12345678112-01-01T01:01:01Z', TimeValue::PRECISION_ka,
				'12345678. millennium BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_ka,
				'12345679. millennium BCE',
			),
			array(
				'-12345671912-01-01T01:01:01Z', TimeValue::PRECISION_10ka,
				'12345670000 years ago',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_10ka,
				'12345680000 years ago',
			),
			array(
				'-12345618912-01-01T01:01:01Z', TimeValue::PRECISION_100ka,
				'12345600000 years ago',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_100ka,
				'12345700000 years ago',
			),
			array(
				'-12345178912-01-01T01:01:01Z', TimeValue::PRECISION_Ma,
				'12345 million years ago',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_Ma,
				'12346 million years ago',
			),
			array(
				'-12341678912-01-01T01:01:01Z', TimeValue::PRECISION_10Ma,
				'12340 million years ago',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_10Ma,
				'12350 million years ago',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_100Ma,
				'12300 million years ago',
			),
			array(
				'-12375678912-01-01T01:01:01Z', TimeValue::PRECISION_100Ma,
				'12400 million years ago',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_Ga,
				'12 billion years ago',
			),
			array(
				'-12545678912-01-01T01:01:01Z', TimeValue::PRECISION_Ga,
				'13 billion years ago',
			),

			// Valid values with day, month and/or year zero
			array(
				'+00000001995-00-00T00:00:00Z', TimeValue::PRECISION_YEAR,
				'1995',
			),
			array(
				'+00000001996-01-00T00:00:00Z', TimeValue::PRECISION_YEAR,
				'1996',
			),
			array(
				'+00000001996-01-00T00:00:00Z', TimeValue::PRECISION_MONTH,
				'January 1996',
			),
			array(
				'+00000001997-00-01T00:00:00Z', TimeValue::PRECISION_YEAR,
				'1997',
			),
			array(
				'+0-00-00T00:00:42Z', TimeValue::PRECISION_YEAR,
				'0',
			),

			// Integer overflows should not happen
			array(
				'+2147483648-00-00T00:00:00Z', TimeValue::PRECISION_YEAR,
				'2147483648',
			),
			array(
				'+9999999999999999-00-00T00:00:00Z', TimeValue::PRECISION_YEAR,
				'9999999999999999',
			),

			// Stuff we do not want to format so must return it :<
			array(
				'-00000000000-01-01T01:01:01Z', TimeValue::PRECISION_Ga,
			),
			array(
				'-0-01-01T01:01:01Z', TimeValue::PRECISION_Ga,
			),
			array(
				'+100000000-00-00T00:00:00Z', TimeValue::PRECISION_Ga,
			),
			array(
				'+10000000-00-00T00:00:01Z', TimeValue::PRECISION_100Ma,
			),
			array(
				'+1000000-00-00T00:00:02Z', TimeValue::PRECISION_10Ma,
			),
			array(
				'+100000-00-00T00:00:03Z', TimeValue::PRECISION_Ma,
			),
			array(
				'+10000-00-00T00:00:04Z', TimeValue::PRECISION_100ka,
			),
			array(
				'+1000-00-00T00:00:05Z', TimeValue::PRECISION_10ka,
			),
			array(
				'+100-00-00T00:00:06Z', TimeValue::PRECISION_ka,
			),
			array(
				'+10-00-00T00:00:07Z', TimeValue::PRECISION_100a,
			),
			array(
				'+1-00-00T00:00:08Z', TimeValue::PRECISION_10a,
			),
			array(
				'-0-00-00T00:00:42Z', TimeValue::PRECISION_YEAR,
			),
			array(
				'+00000002013-07-00T00:00:00Z', TimeValue::PRECISION_DAY,
			),
			array(
				'+10000000000-00-00T00:00:00Z', TimeValue::PRECISION_DAY,
			),
		);

		$argLists = array();

		foreach ( $tests as $args ) {
			$timeValue = new TimeValue(
				$args[0],
				0, 0, 0,
				$args[1],
				TimeFormatter::CALENDAR_GREGORIAN
			);
			$argLists[] = array(
				isset( $args[2] ) ? $args[2] : $args[0],
				$timeValue,
				isset( $args[3] )
			);
		}

		// Different languages at year precision
		$languageCodes = array(
			'ar', //replaces all numbers and separators
			'bo', //replaces only numbers
			'de', //switches separators
			'or', //replaces all numbers and separators
		);
		foreach ( $languageCodes as $languageCode ) {
			$argLists[] = array(
				'3333',
				new TimeValue(
					'+0000000000003333-01-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_YEAR,
					TimeFormatter::CALENDAR_GREGORIAN
				),
				false,
				$languageCode
			);
		}

		return $argLists;
	}

	/**
	 * @dataProvider formatProvider
	 *
	 * @param string $expected
	 * @param TimeValue $timeValue
	 * @param bool $roundtrip
	 * @param string $languageCode
	 */
	public function testFormat(
		$expected,
		TimeValue $timeValue,
		$roundtrip = false,
		$languageCode = 'en'
	) {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $languageCode
		) );
		$formatter = new MwTimeIsoFormatter( $options );
		$actual = $formatter->format( $timeValue );

		$this->assertEquals( $expected, $actual );
		if ( $roundtrip ) {
			$this->assertCanRoundTrip( $actual, $timeValue, $languageCode );
		}
	}

	private function assertCanRoundTrip( $formattedTime, TimeValue $timeValue, $languageCode ) {
		$options = new ParserOptions( array(
			ValueParser::OPT_LANG => $languageCode,
			\ValueParsers\TimeParser::OPT_PRECISION => $timeValue->getPrecision(),
			\ValueParsers\TimeParser::OPT_CALENDAR => $timeValue->getCalendarModel(),
		) );

		$timeParser = new TimeParser( $options );
		$parsedTimeValue = $timeParser->parse( $formattedTime );

		/**
		 * TODO: all of the below can be removed once TimeValue has an equals method
		 */
		$parsedTime = $parsedTimeValue->getTime();
		$expectedTime = $timeValue->getTime();
		$this->assertRegExp(
			'/^' . preg_quote( substr( $expectedTime, 0, 1 ), '/' ) . '0*' . preg_quote( substr( $expectedTime, 1 ), '/' ) . '$/',
			$parsedTime
		);
		$this->assertEquals( $timeValue->getBefore(), $parsedTimeValue->getBefore() );
		$this->assertEquals( $timeValue->getAfter(), $parsedTimeValue->getAfter() );
		$this->assertEquals( $timeValue->getPrecision(), $parsedTimeValue->getPrecision() );
		$this->assertEquals( $timeValue->getTimezone(), $parsedTimeValue->getTimezone() );
		$this->assertEquals( $timeValue->getCalendarModel(), $parsedTimeValue->getCalendarModel() );
	}

}
