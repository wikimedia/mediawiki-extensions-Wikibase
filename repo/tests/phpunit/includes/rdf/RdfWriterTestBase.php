<?php

namespace Wikibase\Test;

use Wikibase\RDF\RdfWriter;
use Wikibase\RDF\RdfWriterBase;

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
		if ( !file_exists( $path ) ) {
			file_put_contents( $path . '.actual', join( "\n", $actual ) );
		}

		$expected = file( $path );

		$expected = $this->normalizeLines( $expected );
		$actual = $this->normalizeLines( $actual );

		$this->assertEquals( $expected, $actual, 'Result mismatches data in ' . $path );
	}

	/**
	 * @return RdfWriter
	 */
	protected abstract function newWriter();

	public function testTriples() {
		$writer = $this->newWriter( RdfWriterBase::SUBDOCUMENT_ROLE );

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );

		$writer->about( 'http://foobar.test/Bananas' )
			->say( 'a' )->is( 'http://foobar.test/Fruit' ); // shorthand name "a"

		// interspersed prefix definition
		$writer->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$writer->about( 'acme', 'Nuts' )
			->say( 'acme', 'weight' )->value( '5.5', 'xsd', 'decimal' );

		// redundant about( 'acme', 'Nuts' )
		$writer->about( 'acme', 'Nuts' )
			->say( 'acme', 'color' )->value( 'brown' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'Triples', $rdf );
	}

	public function testPredicates() {
		$writer = $this->newWriter( RdfWriterBase::SUBDOCUMENT_ROLE );
		$writer->start();
		$writer->prefix( '', 'http://acme.test/' ); // empty prefix
		$writer->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$writer->about( 'http://foobar.test/Bananas' )
			->a( 'http://foobar.test/Fruit' ) // shorthand function a()
			->say( '', 'name' ) // empty prefix
				->text( 'Banana' )
			->say( '', 'name' ) // redundant say( '', 'name' )
				->text( 'Banane', 'de' );

		$writer->about( 'http://foobar.test/Apples' )
			->say( '', 'name' ) // subsequent call to say( '', 'name' ) for a different subject
				->text( 'Apple' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'Predicates', $rdf );
	}

	public function testValues() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );

		$writer->about( 'http://foobar.test/Bananas' )
			->say( 'acme', 'multi' )
				->value( 'A' )
				->value( 'B' )
				->value( 'C' )
			->say( 'acme', 'type' )
				->value( 'foo', 'acme', 'thing' )
				->value( '-5', 'xsd', 'integer' )
				->value( '-5', 'xsd', 'decimal' )
				->value( '-5', 'xsd', 'double' )
				->value( 'true', 'xsd', 'boolean' )
				->value( 'false', 'xsd', 'boolean' )
			->say( 'acme', 'autotype' )
				->value( -5 )
				->value( 3.14 )
				->value( true )
				->value( false )
			->say( 'acme', 'no-autotype' )
				->value( -5, 'xsd', 'decimal' )
				->value( 3.14, 'xsd', 'string' )
				->value( true, 'xsd', 'string' )
				->value( false, 'xsd', 'string' )
			->say( 'acme', 'shorthand' )->value( 'foo' )
			->say( 'acme', 'typed-shorthand' )->value( 'foo', 'acme', 'thing' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'Values', $rdf );
	}

	public function testResources() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );

		$writer->about( 'acme', 'Bongos' )
			->say( 'acme', 'sounds' )
				->is( 'acme', 'Bing' )
				->is( 'http://foobar.test/sound/Bang' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'Resources', $rdf );

	}

	public function testTexts() {
		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );

		$writer->about( 'acme', 'Bongos' )
			->say( 'acme', 'sounds' )
				->text( 'Bom', 'de' )
				->text( 'Bam', 'en' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'Texts', $rdf );
	}

	public function testNumbers() {
		$writer = $this->newWriter( RdfWriterBase::SUBDOCUMENT_ROLE );

		$writer->start();
		$writer->prefix( 'acme', 'http://acme.test/' );
		$writer->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$writer->about( 'acme', 'Bongos' )
			->say( 'acme', 'stock' )->value( 5, 'xsd', 'integer' )
				->value( 7 )
		->about( 'acme', 'Tablas' )
			->say( 'acme', 'stock' )->value( 6 );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'Numbers', $rdf );
	}

	public function testEricMiller() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$writer = $this->newWriter();

		$writer->start();
		$writer->prefix( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		$writer->prefix( 'contact', 'http://www.w3.org/2000/10/swap/pim/contact#' );

		$writer->about( 'http://www.w3.org/People/EM/contact#me' )
			->say( 'rdf', 'type' )->is( 'contact', 'Person' )
			->say( 'contact', 'fullName' )->text( 'Eric Miller' )
			->say( 'contact', 'mailbox' )->is( 'mailto:em@w3.org' )
			->say( 'contact', 'personalTitle' )->text( 'Dr.' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'EricMiller', $rdf );
	}

	public function testLabeledBlankNode() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$writer = $this->newWriter( RdfWriterBase::SUBDOCUMENT_ROLE );

		$writer->start();
		$writer->prefix( 'exterms', 'http://www.example.org/terms/' );
		$writer->prefix( 'exstaff', 'http://www.example.org/staffid/' );

		$writer->about( 'exstaff', '85740' )
			->say( 'exterms', 'address' )->is( '_', $label = $writer->blank( 'johnaddress' ) )
		->about( '_', $label )
			->say( 'exterms', 'street' )->text( "1501 Grant Avenue" )
			->say( 'exterms', 'city' )->text( "Bedfort" )
			->say( 'exterms', 'state' )->text( "Massachusetts" )
			->say( 'exterms', 'postalCode' )->text( "01730" );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'LabeledBlankNode', $rdf );
	}

	public function testNumberedBlankNodes() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$writer = $this->newWriter( RdfWriterBase::SUBDOCUMENT_ROLE );

		$writer->start();
		$writer->prefix( 'exterms', 'http://www.example.org/terms/' );
		$writer->prefix( 'exstaff', 'http://www.example.org/staffid/' );
		$writer->prefix( 'ex', 'http://example.org/packages/vocab#' );

		$writer->about( 'exstaff', 'Sue' )
			->say( 'exterms', 'publication' )->is( '_', $label1 = $writer->blank() );
		$writer->about( '_', $label1 )
			->say( 'exterms', 'title' )->text( 'Antology of Time' );

		$writer->about( 'exstaff', 'Jack' )
			->say( 'exterms', 'publication' )->is( '_', $label2 = $writer->blank() );
		$writer->about( '_', $label2 )
			->say( 'exterms', 'title' )->text( 'Anthony of Time' );

		$rdf = $writer->drain();
		$this->assertOutputLines( 'NumberedBlankNode', $rdf );
	}

	//FIXME: test quoting/escapes!
	//FIXME: test non-ascii literals!
	//FIXME: test uerl-encoding
	//FIXME: test IRIs!
}
