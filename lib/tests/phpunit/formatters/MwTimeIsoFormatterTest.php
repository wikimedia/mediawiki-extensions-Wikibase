<?php

namespace ValueFormatters\Test;

use ValueFormatters\ValueFormatter;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\MwTimeIsoFormatter;

/**
 * @covers ValueFormatters\TimeFormatter
 *
 * @since 0.4
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
class MwTimeIsoFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns an array of test parameters.
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function formatDateProvider() {
		$tests = array(
			'16 July 2013' => array(
				'+00000002013-07-16T00:00:00Z',
				11,
			),
			'1 January 0000' => array(
				'+00000000000-01-01T00:00:00Z',
				11,
			),
			'14 January 0001' => array(
				'+00000000001-01-14T00:00:00Z',
				11,
			),
			'+00000010000-01-01T00:00:00Z' => array(
				'+00000010000-01-01T00:00:00Z',
				11,
			),
			'-00000000001-01-01T00:00:00Z' => array(
				'-00000000001-01-01T00:00:00Z',
				11,
			),
			'July 2013' => array(
				'+00000002013-07-16T00:00:00Z',
				10,
			),
			'2013' => array(
				'+00000002013-07-16T00:00:00Z',
				9,
			),
			'+00000002013-07-16T00:00:00Z' => array(
				'+00000002013-07-16T00:00:00Z',
				8,
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
	 * @since 0.4
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
