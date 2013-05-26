<?php

namespace Wikibase\Test;
use Wikibase\HttpAcceptNegotiator;
use Wikibase\TermIndex;
use Wikibase\ItemContent;
use Wikibase\Item;
use Wikibase\Term;

/**
 * Test for HttpAcceptNegotiator
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
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @covers HttpAcceptNegotiator
 */
class HttpAcceptNegotiatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideGetFirstSupportedValue() {
		return array(
			array( // #0: empty
				array( ), // supported
				array( ), // accepted
				null, // default
				null,  // expected
			),
			array( // #1: simple
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/xzy', 'text/bar' ), // accepted
				null, // default
				'text/BAR',  // expected
			),
			array( // #2: default
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/xzy', 'text/xoo' ), // accepted
				'X', // default
				'X',  // expected
			),
			array( // #3: preference
				array( 'text/foo', 'text/bar', 'application/zuul' ), // supported
				array( 'text/xoo', 'text/BAR', 'text/foo' ), // accepted
				null, // default
				'text/bar',  // expected
			),
			array( // #4: * wildcard
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/xoo', '*' ), // accepted
				null, // default
				'text/foo',  // expected
			),
			array( // #5: */* wildcard
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/xoo', '*/*' ), // accepted
				null, // default
				'text/foo',  // expected
			),
			array( // #6: text/* wildcard
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'application/*', 'text/foo' ), // accepted
				null, // default
				'application/zuul',  // expected
			),
		);
	}

	/**
	 * @dataProvider provideGetFirstSupportedValue
	 */
	public function testGetFirstSupportedValue( $supported, $accepted, $default, $expected ) {
		$negotiator = new HttpAcceptNegotiator( $supported );
		$actual = $negotiator->getFirstSupportedValue( $accepted, $default );

		$this->assertEquals( $expected, $actual );
	}

	public static function provideGetBestSupportedKey() {
		return array(
			array( // #0: empty
				array( ), // supported
				array( ), // accepted
				null, // default
				null,  // expected
			),
			array( // #1: simple
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/xzy' => 1, 'text/bar' => 0.5 ), // accepted
				null, // default
				'text/BAR',  // expected
			),
			array( // #2: default
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/xzy' => 1, 'text/xoo' => 0.5 ), // accepted
				'X', // default
				'X',  // expected
			),
			array( // #3: weighted
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/foo' => 0.3, 'text/BAR' => 0.8, 'application/zuul' => 0.5 ), // accepted
				null, // default
				'text/BAR',  // expected
			),
			array( // #4: zero weight
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/foo' => 0, 'text/xoo' => 1 ), // accepted
				null, // default
				null,  // expected
			),
			array( // #5: * wildcard
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/xoo' => 0.5, '*' => 0.1 ), // accepted
				null, // default
				'text/foo',  // expected
			),
			array( // #6: */* wildcard
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/xoo' => 0.5, '*/*' => 0.1 ), // accepted
				null, // default
				'text/foo',  // expected
			),
			array( // #7: text/* wildcard
				array( 'text/foo', 'text/BAR', 'application/zuul' ), // supported
				array( 'text/foo' => 0.3, 'application/*' => 0.8 ), // accepted
				null, // default
				'application/zuul',  // expected
			),
		);
	}

	/**
	 * @dataProvider provideGetBestSupportedKey
	 */
	public function testGetBestSupportedKey( $supported, $accepted, $default, $expected ) {
		$negotiator = new HttpAcceptNegotiator( $supported );
		$actual = $negotiator->getBestSupportedKey( $accepted, $default );

		$this->assertEquals( $expected, $actual );
	}

}
