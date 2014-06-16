<?php

namespace Wikibase\Test;
use Wikibase\Repo\View\TextInjector;

/**
 * @covers Wikibase\TextInjector
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TextInjectorTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$injector = new TextInjector();
		$this->assertEmpty( $injector->getMarkers() );

		$injector = new TextInjector( array( 'test' => array( 'foo', 'bar' ) ) );
		$this->assertArrayHasKey( 'test', $injector->getMarkers() );
	}

	public function testNewMarker() {
		$injector = new TextInjector();

		$foo = $injector->newMarker( 'foo' );
		$bar = $injector->newMarker( 'bar', 1, 2, 3 );

		$markers = $injector->getMarkers();

		$this->assertArrayHasKey( $foo, $markers );
		$this->assertEquals( array( 'foo' ), $markers[$foo] );

		$this->assertArrayHasKey( $bar, $markers );
		$this->assertEquals( array( 'bar', 1, 2, 3 ), $markers[$bar] );
	}

	public function testInject() {
		$injector = new TextInjector();

		$text = 'Good ' . $injector->newMarker( 'morning' )
			. ' to ' . $injector->newMarker( 'you', 'all' ) . '!';

		$expected = 'Good morning to you all!';

		$actual = $injector->inject( $text, function () {
			$args = func_get_args();
			return implode( ' ', $args );
		} );

		$this->assertEquals( $expected, $actual );
	}

}
