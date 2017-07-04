<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Wikibase\Repo\ParserOutput\TextInjector;

/**
 * @covers Wikibase\Repo\ParserOutput\TextInjector
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class TextInjectorTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$injector = new TextInjector();
		$this->assertEmpty( $injector->getMarkers() );

		$injector = new TextInjector( [ 'test' => [ 'foo', 'bar' ] ] );
		$this->assertArrayHasKey( 'test', $injector->getMarkers() );
	}

	public function testNewMarker() {
		$injector = new TextInjector();

		$foo = $injector->newMarker( 'foo' );
		$bar = $injector->newMarker( 'bar' );

		$markers = $injector->getMarkers();

		$this->assertArrayHasKey( $foo, $markers );
		$this->assertEquals( [ 'foo' ], $markers[$foo] );

		$this->assertArrayHasKey( $bar, $markers );
		$this->assertEquals( [ 'bar' ], $markers[$bar] );
	}

	public function testInject() {
		$injector = new TextInjector();

		$text = 'Good ' . $injector->newMarker( 'morning' )
			. ' to ' . $injector->newMarker( 'you' ) . '!';

		$expected = 'Good morning to you!';

		$actual = $injector->inject( $text, function () {
			$args = func_get_args();
			return implode( ' ', $args );
		} );

		$this->assertEquals( $expected, $actual );
	}

}
