<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\TimeValue;
use MediaWikiIntegrationTestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\MwTimeIsoFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\MwTimeIsoFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MwTimeIsoFormatterTest extends MediaWikiIntegrationTestCase {

	public function formatProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';

		$tests = [
			// Positive dates
			[
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 August 2013',
			],
			[
				'+00000002013-07-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 July 2013',
			],
			[
				'+00000000001-01-14T00:00:00Z', TimeValue::PRECISION_DAY,
				'14 January 1 CE',
			],
			[
				'+00000010000-01-01T00:00:00Z', TimeValue::PRECISION_DAY,
				'1 January 10000',
			],
			[
				'+00000002013-07-16T00:00:00Z', TimeValue::PRECISION_MONTH,
				'July 2013',
			],
			[
				'+00000002013-07-16T00:00:00Z', TimeValue::PRECISION_YEAR,
				'2013',
			],
			[
				'+00000000013-07-16T00:00:00Z', TimeValue::PRECISION_YEAR,
				'13 CE',
			],
			[
				'+00002222013-07-16T00:10:00Z', TimeValue::PRECISION_YEAR,
				'2222013',
			],
			[
				'+12342222013-07-16T00:10:00Z', TimeValue::PRECISION_YEAR,
				'12342222013',
			],

			// Rounding for decades is different from rounding for centuries
			[
				'+1982-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'1980s',
			],
			[
				'+1988-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'1980s',
			],
			[
				'-1982-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'1980s BCE',
			],
			[
				'-1988-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'1980s BCE',
			],

			[
				'+1822-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'19. century',
			],
			[
				'+1822-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'19. century',
			],
			[
				'-1888-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'19. century BCE',
			],
			[
				'-1888-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'19. century BCE',
			],

			[
				'+1222-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'2. millennium',
			],
			[
				'+1888-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'2. millennium',
			],
			[
				'-1222-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'2. millennium BCE',
			],

			// So what about the "Millenium Disagreement"?
			[
				'+1600-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'16. century',
			],
			[
				'+2000-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'2. millennium',
			],

			// Positive dates, stepping through precisions
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'12345678910s',
			],
			[
				'+12345678919-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'12345678910s',
			],
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'123456790. century',
			],
			[
				'+12345678992-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'123456790. century',
			],
			[
				'+12345678112-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. millennium',
			],
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. millennium',
			],
			[
				'+12345671912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10K,
				'12345670000 years CE',
			],
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10K,
				'12345680000 years CE',
			],
			[
				'+12345618912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100K,
				'12345600000 years CE',
			],
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100K,
				'12345700000 years CE',
			],
			[
				'+12345178912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1M,
				'12345 million years CE',
			],
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1M,
				'12346 million years CE',
			],
			[
				'+12341678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10M,
				'12340 million years CE',
			],
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10M,
				'12350 million years CE',
			],
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100M,
				'12300 million years CE',
			],
			[
				'+12375678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100M,
				'12400 million years CE',
			],
			[
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'12 billion years CE',
			],
			[
				'+12545678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'13 billion years CE',
			],

			// Negative dates
			[
				'-2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 August 2013 BCE',
			],
			[
				'-00000002013-07-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 July 2013 BCE',
			],
			[
				'-00000000001-01-14T00:00:00Z', TimeValue::PRECISION_DAY,
				'14 January 1 BCE',
			],
			[
				'-00000010000-01-01T00:00:00Z', TimeValue::PRECISION_DAY,
				'1 January 10000 BCE',
			],
			[
				'-00000002013-07-16T00:00:00Z', TimeValue::PRECISION_MONTH,
				'July 2013 BCE',
			],
			[
				'-00000002013-07-16T00:00:00Z', TimeValue::PRECISION_YEAR,
				'2013 BCE',
			],
			[
				'-00000000013-07-16T00:00:00Z', TimeValue::PRECISION_YEAR,
				'13 BCE',
			],
			[
				'-00002222013-07-16T00:10:00Z', TimeValue::PRECISION_YEAR,
				'2222013 BCE',
			],
			[
				'-12342222013-07-16T00:10:00Z', TimeValue::PRECISION_YEAR,
				'12342222013 BCE',
			],

			// Negative dates, stepping through precisions
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'12345678910s BCE',
			],
			[
				'-12345678919-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'12345678910s BCE',
			],
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'123456790. century BCE',
			],
			[
				'-12345678992-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'123456790. century BCE',
			],
			[
				'-12345678112-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. millennium BCE',
			],
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. millennium BCE',
			],
			[
				'-12345671912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10K,
				'12345670000 years BCE',
			],
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10K,
				'12345680000 years BCE',
			],
			[
				'-12345618912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100K,
				'12345600000 years BCE',
			],
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100K,
				'12345700000 years BCE',
			],
			[
				'-12345178912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1M,
				'12345 million years BCE',
			],
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1M,
				'12346 million years BCE',
			],
			[
				'-12341678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10M,
				'12340 million years BCE',
			],
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10M,
				'12350 million years BCE',
			],
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100M,
				'12300 million years BCE',
			],
			[
				'-12375678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100M,
				'12400 million years BCE',
			],
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'12 billion years BCE',
			],
			[
				'-12545678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'13 billion years BCE',
			],

			// Some languages default to genitive month names
			[
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				// Nominative is "Augustus", genitive is "Augusti".
				'16 Augusti 2013',
				'la',
			],

			// Preserve punctuation as given in MessagesXx.php but skip suffixes and words
			[
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16. augusztus 2013',
				'hu',
			],
			[
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 Avgust, 2013',
				'kaa',
			],
			[
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 agosto 2013',
				'pt',
			],
			[
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 8 2013',
				'yue',
			],
			[
				'+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 aŭg. 2013',
				'eo',
			],

			// Valid values with day, month and/or year zero
			[
				'+00000001995-00-00T00:00:00Z', TimeValue::PRECISION_YEAR,
				'1995',
			],
			[
				'+00000001996-01-00T00:00:00Z', TimeValue::PRECISION_YEAR,
				'1996',
			],
			[
				'+5-01-00T00:00:00Z', TimeValue::PRECISION_MONTH,
				'January 5 CE',
			],
			[
				'+00000001996-01-00T00:00:00Z', TimeValue::PRECISION_MONTH,
				'January 1996',
			],
			[
				'+00000001997-01-01T00:00:00Z', TimeValue::PRECISION_YEAR,
				'1997',
			],
			[
				'+0-01-01T00:00:42Z', TimeValue::PRECISION_YEAR,
				'0 CE',
			],

			// centuries and millenia start with 1, so we can format "low" years just fine
			[
				'+100-01-01T00:00:06Z', TimeValue::PRECISION_YEAR1K,
				'1. millennium',
			],
			[
				'-100-01-01T00:00:06Z', TimeValue::PRECISION_YEAR1K,
				'1. millennium BCE',
			],
			[
				'+10-01-01T00:00:07Z', TimeValue::PRECISION_YEAR100,
				'1. century',
			],

			// Integer overflows should not happen
			[
				'+2147483648-00-00T00:00:00Z', TimeValue::PRECISION_YEAR,
				'2147483648',
			],

			// No exponents (e.g. 1.0E+16) please
			[
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_YEAR10K,
				'10000000000000000 years CE',
			],
			[
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_YEAR100,
				'100000000000000. century',
			],
			[
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_YEAR,
				'9999999999999999',
			],
			[
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_MONTH,
				'January 9999999999999999',
			],
			[
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_DAY,
				'1 January 9999999999999999',
			],

			// Precision to low, falling back to year
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G,
				'1 BCE',
			],
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100M,
				'1 BCE',
			],
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10M,
				'1 BCE',
			],
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M,
				'1 BCE',
			],
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100K,
				'1 BCE',
			],
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10K,
				'1 BCE',
			],
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K,
				'1. millennium BCE',
			],
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100,
				'1. century BCE',
			],
			[
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10,
				'1 BCE',
			],
			[
				'+2015-01-01T01:00:00Z', TimeValue::PRECISION_HOUR,
				'+2015-01-01T01:00:00Z',
			],

			// Better than the raw ISO string
			[
				'-00000000000-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'0 BCE',
			],
			[
				'-0-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'0 BCE',
			],
			[
				'+100000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G,
				'100000000',
			],
			[
				'+10000000-01-01T00:00:01Z', TimeValue::PRECISION_YEAR100M,
				'10000000',
			],
			[
				'+1000000-01-01T00:00:02Z', TimeValue::PRECISION_YEAR10M,
				'1000000',
			],
			[
				'+100000-01-01T00:00:03Z', TimeValue::PRECISION_YEAR1M,
				'100000',
			],
			[
				'+10000-01-01T00:00:04Z', TimeValue::PRECISION_YEAR100K,
				'10000',
			],
			[
				'+1000-01-01T00:00:05Z', TimeValue::PRECISION_YEAR10K,
				'1000',
			],
			[
				'+1-01-01T00:00:08Z', TimeValue::PRECISION_YEAR10,
				'1 CE',
			],
			[
				'-0-01-01T00:00:42Z', TimeValue::PRECISION_YEAR,
				'0 BCE',
			],

			// Stuff we do not want to format so must return it :<
			[
				'+2013-07-00T00:00:00Z', TimeValue::PRECISION_DAY,
			],
			[
				'+10000000000-00-00T00:00:00Z', TimeValue::PRECISION_DAY,
			],

			// Localization of precision >= PRECISION_YEAR works
			[
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'12 Milliarden Jahre v. Chr.',
				'de',
			],
			[
				'+12345678112-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. Jahrtausend',
				'de',
			],
			[
				'+10000000-01-01T00:00:01Z', TimeValue::PRECISION_YEAR100M,
				'10000000',
				'de',
			],

			// Spanish has no date preferences
			[
				'+2017-01-16T00:00:00Z', TimeValue::PRECISION_DAY,
				'16 ene 2017',
				'es',
			],
		];

		foreach ( $tests as $args ) {
			$timestamp = $args[0];
			$precision = $args[1];
			$expected = $args[2] ?? $timestamp;
			$languageCode = $args[3] ?? 'en';

			yield [
				$expected,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $gregorian ),
				$languageCode,
			];
		}

		// Different languages at year precision
		$languageCodes = [
			'ar', //replaces all numbers and separators
			'bo', //replaces only numbers
			'de', //switches separators
			'es', //no date preferences
			'la', //defaults to genitive month names
			'or', //replaces all numbers and separators
		];

		foreach ( $languageCodes as $languageCode ) {
			yield [
				'3333',
				new TimeValue(
					'+0000000000003333-01-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_YEAR,
					$gregorian
				),
				$languageCode,
			];
		}
	}

	/**
	 * @dataProvider formatProvider
	 *
	 * @param string $expected
	 * @param TimeValue $timeValue
	 * @param string $languageCode
	 */
	public function testFormat(
		$expected,
		TimeValue $timeValue,
		$languageCode = 'en'
	) {
		$options = new FormatterOptions( [
			ValueFormatter::OPT_LANG => $languageCode,
		] );
		$formatter = new MwTimeIsoFormatter( $this->getServiceContainer()->getLanguageFactory(), $options );
		$actual = $formatter->format( $timeValue );

		$this->assertSame( $expected, $actual, 'Testing ' . $timeValue->getTime() . ', precision ' . $timeValue->getPrecision() );
	}

}
