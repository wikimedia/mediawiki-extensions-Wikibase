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
			'1 January 0000' => array(
				'+00000000000-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'14 January 0001' => array(
				'+00000000001-01-14T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'1 January 10,000' => array(
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
			'0013' => array(
				'+00000000013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'2,222,013' => array(
				'+00002222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'12,342,222,013' => array(
				'+12342222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			//stepping through precisions
			'12,345,678,910s' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'12,345,678,920s' => array(
				'+12345678919-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'123,456,789. century' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'123,456,790. century' => array(
				'+12345678992-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'12,345,678. millennium' => array(
				'+12345678112-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'12,345,679. millennium' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'in 12,345,670,000 years' => array(
				'+12345671912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'in 12,345,680,000 years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'in 12,345,600,000 years' => array(
				'+12345618912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'in 12,345,700,000 years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'in 12,345 million years' => array(
				'+12345178912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'in 12,346 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'in 12,340 million years' => array(
				'+12341678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'in 12,350 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'in 12,300 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'in 12,400 million years' => array(
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
			//The below should still return the full timestamp as we can not yet format
			'-00000000001-01-01T00:00:00Z' => array(
				'-00000000001-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
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
