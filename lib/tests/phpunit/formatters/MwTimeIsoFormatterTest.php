<?php

namespace ValueFormatters\Test;
use Wikibase\Lib\MwTimeIsoFormatter;

/**
 * Unit tests for the ValueFormatters\TimeFormatter class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLibTest
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
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
		$isoFormatter = new MwTimeIsoFormatter( \Language::factory( 'en' ) );
		$this->assertEquals( $expected, $isoFormatter->formatDate( $extendedIsoString, $precision ) );
	}

}
