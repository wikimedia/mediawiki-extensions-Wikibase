<?php

namespace Wikibase\Test;

use Wikibase\Repo\LinkedData\HttpAcceptNegotiator;

/**
 * @covers Wikibase\Repo\LinkedData\HttpAcceptNegotiator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class HttpAcceptNegotiatorTest extends \PHPUnit_Framework_TestCase {

	public function provideGetFirstSupportedValue() {
		return array(
			array( // #0: empty
				array(), // supported
				array(), // accepted
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

	public function provideGetBestSupportedKey() {
		return array(
			array( // #0: empty
				array(), // supported
				array(), // accepted
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
			array( // #8: Test specific format preferred over wildcard (T133314)
				array( 'application/rdf+xml', 'text/json', 'text/html' ), // supported
				array( '*/*' => 1, 'text/html' => 1 ), // accepted
				null, // default
				'text/html',  // expected
			),
			array( // #9: Test specific format preferred over range (T133314)
				array( 'application/rdf+xml', 'text/json', 'text/html' ), // supported
				array( 'text/*' => 1, 'text/html' => 1 ), // accepted
				null, // default
				'text/html',  // expected
			),
			array( // #10: Test range preferred over wildcard (T133314)
				array( 'application/rdf+xml', 'text/html' ), // supported
				array( '*/*' => 1, 'text/*' => 1 ), // accepted
				null, // default
				'text/html',  // expected
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
