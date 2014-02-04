<?php

namespace ValueFormatters\Test;

use DataValues\TimeValue;
use ValueFormatters\ValueFormatter;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\MwTimeIsoFormatter;

/**
 * @covers ValueFormatters\TimeFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Adam Shorland
 */
class MwTimeIsoFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns an array of test parameters.
	 *
	 * @return array
	 */
	public function formatDateProvider() {
		$tests = array(
			'16 July 2013' => array(
				'+00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'14 January 1' => array(
				'+00000000001-01-14T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'1 January 10000' => array(
				'+00000010000-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'July 2013' => array(
				'+00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_MONTH,
			),
			'2013' => array(
				'+00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'13' => array(
				'+00000000013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'2222013' => array(
				'+00002222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'12342222013' => array(
				'+12342222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			//stepping through precisions
			'12345678910s' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'12345678920s' => array(
				'+12345678919-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'123456789. century' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'123456790. century' => array(
				'+12345678992-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'12345678. millennium' => array(
				'+12345678112-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'12345679. millennium' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'in 12345670000 years' => array(
				'+12345671912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'in 12345680000 years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'in 12345600000 years' => array(
				'+12345618912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'in 12345700000 years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'in 12345 million years' => array(
				'+12345178912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'in 12346 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'in 12340 million years' => array(
				'+12341678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'in 12350 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'in 12300 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'in 12400 million years' => array(
				'+12375678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'in 12 billion years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),
			'in 13 billion years' => array(
				'+12545678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),

			'16 July 2013 BCE' => array(
				'-00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'14 January 1 BCE' => array(
				'-00000000001-01-14T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'1 January 10000 BCE' => array(
				'-00000010000-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'July 2013 BCE' => array(
				'-00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_MONTH,
			),
			'2013 BCE' => array(
				'-00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'13 BCE' => array(
				'-00000000013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'2222013 BCE' => array(
				'-00002222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'12342222013 BCE' => array(
				'-12342222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			//stepping through precisions
			'12345678910s BCE' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'12345678920s BCE' => array(
				'-12345678919-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'123456789. century BCE' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'123456790. century BCE' => array(
				'-12345678992-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'12345678. millennium BCE' => array(
				'-12345678112-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'12345679. millennium BCE' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'12345670000 years ago' => array(
				'-12345671912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'12345680000 years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'12345600000 years ago' => array(
				'-12345618912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'12345700000 years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'12345 million years ago' => array(
				'-12345178912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'12346 million years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'12340 million years ago' => array(
				'-12341678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'12350 million years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'12300 million years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'12400 million years ago' => array(
				'-12375678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'12 billion years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),
			'13 billion years ago' => array(
				'-12545678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),
		);

		$argLists = array();

		foreach ( $tests as $expected => $args ) {
			$argLists[] = array( $expected, $args[0], $args[1] );
		}

		return $argLists;
	}

	/**
	 * @dataProvider formatDateProvider
	 *
	 * @param string $expected
	 * @param string $extendedIsoString
	 * @param integer $precision
	 */
	public function testFormatDate( $expected, $extendedIsoString, $precision ) {
		$langCode = 'en';
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $langCode
		) );

		$isoFormatter = new MwTimeIsoFormatter( $options );

		$this->assertEquals( $expected, $isoFormatter->formatDate( $extendedIsoString, $precision ) );
	}

}
