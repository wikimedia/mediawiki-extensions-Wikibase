<?php

namespace Wikibase\Lib\Tests;

use DataValues\TimeValue;
use MediaWikiTestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\MwTimeIsoFormatter;

/**
 * @covers Wikibase\Lib\MwTimeIsoFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Addshore
 * @author Thiemo Mättig
 */
class MwTimeIsoFormatterTest extends MediaWikiTestCase {

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

			// Rounding for decades is different from rounding for centuries
			array(
				'+1982-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'1980s',
			),
			array(
				'+1988-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'1980s',
			),
			array(
				'-1982-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'1980s BCE',
			),
			array(
				'-1988-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'1980s BCE',
			),

			array(
				'+1822-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'19. century',
			),
			array(
				'+1822-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'19. century',
			),
			array(
				'-1888-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'19. century BCE',
			),
			array(
				'-1888-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'19. century BCE',
			),

			array(
				'+1222-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'2. millennium',
			),
			array(
				'+1888-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'2. millennium',
			),
			array(
				'-1222-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'2. millennium BCE',
			),

			// So what about the "Millenium Disagreement"?
			array(
				'+1600-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'16. century',
			),
			array(
				'+2000-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'2. millennium',
			),

			// Positive dates, stepping through precisions
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'12345678910s',
			),
			array(
				'+12345678919-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'12345678910s',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'123456790. century',
			),
			array(
				'+12345678992-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'123456790. century',
			),
			array(
				'+12345678112-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. millennium',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. millennium',
			),
			array(
				'+12345671912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10K,
				'12345670000 years CE',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10K,
				'12345680000 years CE',
			),
			array(
				'+12345618912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100K,
				'12345600000 years CE',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100K,
				'12345700000 years CE',
			),
			array(
				'+12345178912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1M,
				'12345 million years CE',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1M,
				'12346 million years CE',
			),
			array(
				'+12341678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10M,
				'12340 million years CE',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10M,
				'12350 million years CE',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100M,
				'12300 million years CE',
			),
			array(
				'+12375678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100M,
				'12400 million years CE',
			),
			array(
				'+12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'12 billion years CE',
			),
			array(
				'+12545678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'13 billion years CE',
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
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'12345678910s BCE',
			),
			array(
				'-12345678919-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10,
				'12345678910s BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'123456790. century BCE',
			),
			array(
				'-12345678992-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100,
				'123456790. century BCE',
			),
			array(
				'-12345678112-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. millennium BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. millennium BCE',
			),
			array(
				'-12345671912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10K,
				'12345670000 years BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10K,
				'12345680000 years BCE',
			),
			array(
				'-12345618912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100K,
				'12345600000 years BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100K,
				'12345700000 years BCE',
			),
			array(
				'-12345178912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1M,
				'12345 million years BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1M,
				'12346 million years BCE',
			),
			array(
				'-12341678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10M,
				'12340 million years BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR10M,
				'12350 million years BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100M,
				'12300 million years BCE',
			),
			array(
				'-12375678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR100M,
				'12400 million years BCE',
			),
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'12 billion years BCE',
			),
			array(
				'-12545678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'13 billion years BCE',
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
				'+5-01-00T00:00:00Z', TimeValue::PRECISION_MONTH,
				'January 5',
			),
			array(
				'+00000001996-01-00T00:00:00Z', TimeValue::PRECISION_MONTH,
				'January 1996',
			),
			array(
				'+00000001997-01-01T00:00:00Z', TimeValue::PRECISION_YEAR,
				'1997',
			),
			array(
				'+0-00-00T00:00:42Z', TimeValue::PRECISION_YEAR,
				'0',
			),

			// centuries and millenia start with 1, so we can format "low" years just fine
			array(
				'+100-00-00T00:00:06Z', TimeValue::PRECISION_YEAR1K,
				'1. millennium'
			),
			array(
				'-100-00-00T00:00:06Z', TimeValue::PRECISION_YEAR1K,
				'1. millennium BCE'
			),
			array(
				'+10-00-00T00:00:07Z', TimeValue::PRECISION_YEAR100,
				'1. century'
			),

			// Integer overflows should not happen
			array(
				'+2147483648-00-00T00:00:00Z', TimeValue::PRECISION_YEAR,
				'2147483648',
			),

			// No exponents (e.g. 1.0E+16) please
			array(
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_YEAR10K,
				'10000000000000000 years CE',
			),
			array(
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_YEAR100,
				'100000000000000. century',
			),
			array(
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_YEAR,
				'9999999999999999',
			),
			array(
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_MONTH,
				'January 9999999999999999',
			),
			array(
				'+9999999999999999-01-01T00:00:00Z', TimeValue::PRECISION_DAY,
				'1 January 9999999999999999',
			),

			// Precision to low, falling back to year
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G,
				'1 BCE',
			),
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100M,
				'1 BCE',
			),
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10M,
				'1 BCE',
			),
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M,
				'1 BCE',
			),
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100K,
				'1 BCE',
			),
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10K,
				'1 BCE',
			),
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K,
				'1. millennium BCE',
			),
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100,
				'1. century BCE',
			),
			array(
				'-1-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10,
				'1 BCE',
			),
			array(
				'+2015-01-01T01:00:00Z', TimeValue::PRECISION_HOUR,
				'+2015-01-01T01:00:00Z',
			),

			// Better than the raw ISO string
			array(
				'-00000000000-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'0 BCE',
			),
			array(
				'-0-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'0 BCE',
			),
			array(
				'+100000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G,
				'100000000',
			),
			array(
				'+10000000-00-00T00:00:01Z', TimeValue::PRECISION_YEAR100M,
				'10000000',
			),
			array(
				'+1000000-00-00T00:00:02Z', TimeValue::PRECISION_YEAR10M,
				'1000000',
			),
			array(
				'+100000-00-00T00:00:03Z', TimeValue::PRECISION_YEAR1M,
				'100000',
			),
			array(
				'+10000-00-00T00:00:04Z', TimeValue::PRECISION_YEAR100K,
				'10000',
			),
			array(
				'+1000-00-00T00:00:05Z', TimeValue::PRECISION_YEAR10K,
				'1000',
			),
			array(
				'+1-00-00T00:00:08Z', TimeValue::PRECISION_YEAR10,
				'1',
			),
			array(
				'-0-00-00T00:00:42Z', TimeValue::PRECISION_YEAR,
				'0 BCE',
			),

			// Stuff we do not want to format so must return it :<
			array(
				'+2013-07-00T00:00:00Z', TimeValue::PRECISION_DAY,
			),
			array(
				'+10000000000-00-00T00:00:00Z', TimeValue::PRECISION_DAY,
			),

			// Localization of precision >= PRECISION_YEAR works
			array(
				'-12345678912-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1G,
				'12 Milliarden Jahre v. Chr.',
				'de'
			),
			array(
				'+12345678112-01-01T01:01:01Z', TimeValue::PRECISION_YEAR1K,
				'12345679. Jahrtausend',
				'de'
			),
			array(
				'+10000000-00-00T00:00:01Z', TimeValue::PRECISION_YEAR100M,
				'10000000',
				'de'
			),
		);

		$argLists = array();

		foreach ( $tests as $args ) {
			$timestamp = $args[0];
			$precision = $args[1];
			$expected = isset( $args[2] ) ? $args[2] : $timestamp;
			$languageCode = isset( $args[3] ) ? $args[3] : 'en';

			$argLists[] = array(
				$expected,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $gregorian ),
				$languageCode
			);
		}

		// Different languages at year precision
		$languageCodes = array(
			'ar', //replaces all numbers and separators
			'bo', //replaces only numbers
			'de', //switches separators
			'la', //defaults to genitive month names
			'or', //replaces all numbers and separators
		);

		foreach ( $languageCodes as $languageCode ) {
			$argLists[] = array(
				'3333',
				new TimeValue(
					'+0000000000003333-01-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_YEAR,
					$gregorian
				),
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
	 * @param string $languageCode
	 */
	public function testFormat(
		$expected,
		TimeValue $timeValue,
		$languageCode = 'en'
	) {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $languageCode
		) );
		$formatter = new MwTimeIsoFormatter( $options );
		$actual = $formatter->format( $timeValue );

		$this->assertSame( $expected, $actual, 'Testing ' . $timeValue->getTime() . ', precision ' . $timeValue->getPrecision() );
	}

}
