<?php

namespace Wikibase\Test;
use Wikibase\RDF\RdfWriter;

/**
 * Base class for tests for RdfWriter implementations.
 * Provides a common test suite for all implementations.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
abstract class RdfWriterTestBase extends \PHPUnit_Framework_TestCase{

	abstract protected function getFileSuffix();

	protected function getExpectedOutputFile( $datasetName ) {
		$path = __DIR__ . '/../../data/rdf/' . $datasetName . '.' . $this->getFileSuffix();
		return $path;
	}

	private function normalizeLines( array $lines ) {
		$normalized = array();

		foreach ( $lines as $s ) {
			$s = trim( $s, "\r\n" );
			$normalized[] = $s;
		}

		return $normalized;
	}

	protected function assertOutputLines( $datasetName, $actual ) {
		if ( is_string( $actual ) ) {
			$actual = trim( $actual, "\r\n" );
			$actual = explode( "\n", $actual );
		}

		$path = $this->getExpectedOutputFile( $datasetName );

		// Create test data file if it doesn't exist.
		/*if ( !file_exists( $path ) ) {
			file_put_contents( $path . '.actual', join( "\n", $actual ) );
		}*/

		$expected = file( $path );

		$expected = $this->normalizeLines( $expected );
		$actual = $this->normalizeLines( $actual );

		$this->assertEquals( $expected, $actual, 'Result mismatches data in ' . $path );
	}

	/**
	 * @return RdfWriter
	 */
	protected abstract function newWriter();

	public function testTwoTriples() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );

		$writer->about( 'http://foobar.test/Bananas' )
			->say( 'a' )->is( 'http://foobar.test/Fruit' ); // shorthand name "a"

		// interspersed prefix definition
		$writer->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$writer->about( 'acme:Nuts' )
			->say( 'acme:weight' )->value( 5, 'xsd:Number' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'TwoTriples', $rdf );
	}

	public function testTwoPredicates() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( '', 'http://acme.test/' ); // empty prefix
		$writer->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$writer->about( 'http://foobar.test/Bananas' )
			->a( 'http://foobar.test/Fruit' ) // shorthand function a()
			->say( ':weight' ) // empty prefix
				->value( 5, 'xsd:Number' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'TwoPredicates', $rdf );
	}

	public function testTwoValues() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );

		$writer->about( 'http://foobar.test/Bananas' )
			->say( 'acme:has' )
				->is( 'acme:Vitamin-A' )
				->text( 'Vitamin A' )
			->say( 'acme:weight' )
				->value( 5 );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'TwoValues', $rdf );
	}

	public function testTwoResources() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );

		$writer->about( 'acme:Bongos' )
			->say( 'acme:sounds' )
				->is( 'acme:Bing' )
				->is( 'http://foobar.test/sound/Bang' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'TwoResources', $rdf );

	}

	public function testTwoTexts() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );

		$writer->about( 'acme:Bongos' )
			->say( 'acme:sounds' )
				->text( 'Bom', 'de' )
				->text( 'Bam', 'en' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'TwoTexts', $rdf );
	}

	public function testTwoNumbers() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );
		$writer->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$writer->about( 'acme:Bongos' )
			->hasv( 'acme:stock', 5, 'xsd:Number' )
				->value( 7 )
		->about( 'acme:Tablas' )
			->hasv( 'acme:stock', 6 );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'TwoNumbers', $rdf );
	}

	public function testEricMiller() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		$writer->prefix( 'contact', 'http://www.w3.org/2000/10/swap/pim/contact#' );

		$writer->about( 'http://www.w3.org/People/EM/contact#me' )
			->hasr( 'rdf:type', 'contact:Person' )
			->hast( 'contact:fullName', 'Eric Miller' )
			->hasr( 'contact:mailbox','mailto:em@w3.org' )
			->hast( 'contact:personalTitle', 'Dr.' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'EricMiller', $rdf );
	}

	public function testLabeledBlankNode() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'exterms', 'http://www.example.org/terms/' );
		$writer->prefix( 'exstaff', 'http://www.example.org/staffid/' );

		$writer->about( 'exstaff:85740' )
			->say( 'exterms:address' )->is( $label = $writer->blank( 'johnaddress' ) )
		->about( $label )
			->say( 'exterms:street' )->text( "1501 Grant Avenue" )
			->say( 'exterms:city' )->text( "Bedfort" )
			->say( 'exterms:state' )->text( "Massachusetts" )
			->say( 'exterms:postalCode' )->text( "01730" );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'LabeledBlankNode', $rdf );
	}

	public function testNumberedBlankNodes() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'exterms', 'http://www.example.org/terms/' );
		$writer->prefix( 'exstaff', 'http://www.example.org/staffid/' );
		$writer->prefix( 'ex', 'http://example.org/packages/vocab#' );

		$writer->about( 'exstaff:Sue' )
			->say( 'exterms:publication' )->is( $label1 = $writer->blank() );
		$writer->about( $label1 )
			->say( 'exterms:title' )->text( 'Antology of Time' );

		$writer->about( 'exstaff:Jack' )
			->say( 'exterms:publication' )->is( $label2 = $writer->blank() );
		$writer->about( $label2 )
			->say( 'exterms:title' )->text( 'Anthony of Time' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'NumberedBlankNode', $rdf );
	}

	//FIXME: test quoting/escapes!
	//FIXME: test non-ascii literals!
	//FIXME: test uerl-encoding
	//FIXME: test IRIs!
}
