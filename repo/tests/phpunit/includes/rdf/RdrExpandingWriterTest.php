<?php

namespace Wikibase\Test;

use Wikibase\RDF\RdfWriter;
use Wikibase\RDF\RdrExpandingWriter;

/**
 * @covers Wikibase\RDF\RdrExpandingWriter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 * @group RdfWriter
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RdrExpandingWriterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return RdfWriter
	 */
	private function getDirectWriterMock2( $method, $a, $b ) {
		$writer = $this->getMock( 'Wikibase\RDF\RdfWriter' );
		$writer->expects( $this->once() )
			->method( $method )
			->with( $a, $b )
			->will( $this->returnValue( $writer ) );

		return $writer;
	}

	/**
	 * @return RdfWriter
	 */
	private function getDirectWriterMock3( $method, $a, $b, $c ) {
		$writer = $this->getMock( 'Wikibase\RDF\RdfWriter' );
		$writer->expects( $this->once() )
			->method( $method )
			->with( $a, $b, $c )
			->will( $this->returnValue( $writer ) );

		return $writer;
	}

	public function testAbout() {
		$directWriter = $this->getDirectWriterMock2( 'about', '', 'S' );
		$reifiedWriter = $this->getMock( 'Wikibase\RDF\RdfWriter' );

		$reifiedWriter->expects( $this->once() )
			->method( 'blank' )
			->will( $this->returnValue( 'bn1' ) );

		$reifiedWriter->expects( $this->once() )
			->method( 'a' )
			->with( 'rdf', 'Statement' )
			->will( $this->returnValue( $reifiedWriter ) );

		$reifiedWriter->expects( $this->once() )
			->method( 'about' )
			->with( '_', 'bn1' )
			->will( $this->returnValue( $reifiedWriter ) );

		$reifiedWriter->expects( $this->once() )
			->method( 'say' )
			->with( 'rdf', 'Subject' )
			->will( $this->returnValue( $reifiedWriter ) );

		$reifiedWriter->expects( $this->once() )
			->method( 'is' )
			->with( '', 'S' )
			->will( $this->returnValue( $reifiedWriter ) );

		$expandingWriter = new RdrExpandingWriter( $directWriter, $reifiedWriter );
		$expandingWriter->about( '', 'S' );
	}

	public function testSay() {
		$directWriter = $this->getDirectWriterMock2( 'say', '', 'P' );
		$reifiedWriter = $this->getMock( 'Wikibase\RDF\RdfWriter' );

		$reifiedWriter->expects( $this->once() )
			->method( 'say' )
			->with( 'rdf', 'Predicate' )
			->will( $this->returnValue( $reifiedWriter ) );

		$reifiedWriter->expects( $this->once() )
			->method( 'is' )
			->with( '', 'P' )
			->will( $this->returnValue( $reifiedWriter ) );

		$expandingWriter = new RdrExpandingWriter( $directWriter, $reifiedWriter );
		$expandingWriter->say( '', 'P' );
	}

	public function testIs() {
		$directWriter = $this->getDirectWriterMock2( 'is', '', 'O' );
		$reifiedWriter = $this->getMock( 'Wikibase\RDF\RdfWriter' );

		$reifiedWriter->expects( $this->once() )
			->method( 'say' )
			->with( 'rdf', 'Object' )
			->will( $this->returnValue( $reifiedWriter ) );

		$reifiedWriter->expects( $this->once() )
			->method( 'is' )
			->with( '', 'O' )
			->will( $this->returnValue( $reifiedWriter ) );

		$expandingWriter = new RdrExpandingWriter( $directWriter, $reifiedWriter );
		$expandingWriter->is( '', 'O' );
	}

	public function testText() {
		$directWriter = $this->getDirectWriterMock2( 'text', 't', 'en' );
		$reifiedWriter = $this->getMock( 'Wikibase\RDF\RdfWriter' );

		$reifiedWriter->expects( $this->once() )
			->method( 'say' )
			->with( 'rdf', 'Object' )
			->will( $this->returnValue( $reifiedWriter ) );

		$reifiedWriter->expects( $this->once() )
			->method( 'text' )
			->with( 't', 'en' )
			->will( $this->returnValue( $reifiedWriter ) );

		$expandingWriter = new RdrExpandingWriter( $directWriter, $reifiedWriter );
		$expandingWriter->text( 't', 'en' );
	}

	public function testValue() {
		$directWriter = $this->getDirectWriterMock2( 'value', 'v', 'x', 'foo' );
		$reifiedWriter = $this->getMock( 'Wikibase\RDF\RdfWriter' );

		$reifiedWriter->expects( $this->once() )
			->method( 'say' )
			->with( 'rdf', 'Object' )
			->will( $this->returnValue( $reifiedWriter ) );

		$reifiedWriter->expects( $this->once() )
			->method( 'value' )
			->with( 'v', 'x', 'foo' )
			->will( $this->returnValue( $reifiedWriter ) );

		$expandingWriter = new RdrExpandingWriter( $directWriter, $reifiedWriter );
		$expandingWriter->value( 'v', 'x', 'foo' );
	}

}
