<?php

namespace Wikibase\Test;
use Wikibase\RDF\RdfEmitter;

/**
 * Base class for tests for RdfEmitter implementations.
 * Provides a common test suite for all implementations.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
abstract class RdfEmitterTestBase extends \PHPUnit_Framework_TestCase{

	abstract protected function getFileSuffix();

	protected function getExpectedOutputFile( $datasetName ) {
		$path = __DIR__ . '/../../data/rdf/' . $datasetName . '.' . $this->getFileSuffix();
		return $path;
	}

	private function normalizeLines( array $lines ) {
		$normalized = array();

		foreach ( $lines as $s ) {
			$s = trim( $s );
			if ( $s !== '' ) {
				$normalized[] = $s;
			}
		}

		return $normalized;
	}

	protected function assertOutputLines( $datasetName, $actual ) {
		if ( is_string( $actual ) ) {
			$actual = explode( "\n", $actual );
		}

		$path = $this->getExpectedOutputFile( $datasetName );

		//if ( !file_exists( $path ) ) {
		//	$ok = file_put_contents( $path . '.actual', join( "\n", $actual ) );
		//}

		$expected = file( $path );

		$expected = $this->normalizeLines( $expected );
		$actual = $this->normalizeLines( $actual );

		$this->assertEquals( $expected, $actual, 'Result mismatches data in ' . $path );
	}

	/**
	 * @return RdfEmitter
	 */
	protected abstract function newEmitter();

	public function testTwoTriples() {
		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'acme', 'http://acme.test/' );
		$emitter->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$emitter->about( 'http://foobar.test/Bananas' )
			->say( 'a' )->is( 'http://foobar.test/Fruit' );

		$emitter->about( 'acme:Nuts' )
			->say( 'http://foobar.test/verb/weight' )->value( 5, 'xsd:Number' );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'TwoTriples', $rdf );
	}

	public function testTwoPredicates() {
		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'acme', 'http://acme.test/' );
		$emitter->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$emitter->about( 'http://foobar.test/Bananas' )
			->say( 'a' )
				->is( 'http://foobar.test/Fruit' )
			->say( 'http://foobar.test/verb/weight' )
				->value( 5, 'xsd:Number' );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'TwoPredicates', $rdf );
	}

	public function testTwoValues() {
		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'acme', 'http://acme.test/' );

		$emitter->about( 'http://foobar.test/Bananas' )
			->say( 'http://foobar.test/verb/has' )
				->is( 'acme:Vitamin-A' )
				->text( 'Vitamin A' )
			->say( 'http://foobar.test/verb/weight' )
				->value( 5 );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'TwoValues', $rdf );
	}

	public function testTwoResources() {
		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'acme', 'http://acme.test/' );

		$emitter->about( 'acme:Bongos' )
			->say( 'acme:sounds' )
				->is( 'acme:Bing' )
				->is( 'http://foobar.test/sound/Bang' );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'TwoResources', $rdf );

	}

	public function testTwoTexts() {
		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'acme', 'http://acme.test/' );

		$emitter->about( 'acme:Bongos' )
			->say( 'acme:sounds' )
				->text( 'Bom', 'de' )
				->text( 'Bam', 'en' );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'TwoTexts', $rdf );
	}

	public function testTwoNumbers() {
		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'acme', 'http://acme.test/' );
		$emitter->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

		$emitter->about( 'acme:Bongos' )
			->say( 'acme:stock' )
				->value( 5, 'xsd:Number' )
				->value( 7 )
			->about( 'acme:Tablas' )
				->say( 'acme:stock' )->value( 6 );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'TwoNumbers', $rdf );
	}

	public function testEricMiller() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		$emitter->prefix( 'contact', 'http://www.w3.org/2000/10/swap/pim/contact#' );

		$emitter->about( 'http://www.w3.org/People/EM/contact#me' )
			->say( 'rdf:type' )->is( 'contact:Person' )
			->say( 'contact:fullName' )->is( 'Eric Miller' )
			->say( 'contact:mailbox' )->is( 'mailto:em@w3.org' )
			->say( 'contact:personalTitle' )->text( 'Dr.' );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'EricMiller', $rdf );
	}

	public function testLabeledBlankNode() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'exterms', 'http://www.example.org/terms/' );
		$emitter->prefix( 'exstaff', 'http://www.example.org/staffid/' );

		$emitter->about( 'extstaff:85740' )
			->say( 'exterms:address' )->is( $label = $emitter->blank( 'johnaddress' ) )
		->about( $label )
			->say( 'exterms:street' )->text( "1501 Grant Avenue" )
			->say( 'exterms:city' )->text( "Bedfort" )
			->say( 'exterms:state' )->text( "Massachusetts" )
			->say( 'exterms:postalCode' )->text( "01730" );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'LabeledBlankNode', $rdf );
	}

	public function testNumberedBlankNodes() {
		// exampel taken from http://www.w3.org/2007/02/turtle/primer/

		$emitter = $this->newEmitter();

		$emitter->start();
		$emitter->prefix( 'exterms', 'http://www.example.org/terms/' );
		$emitter->prefix( 'exstaff', 'http://www.example.org/staffid/' );
		$emitter->prefix( 'ex', 'http://example.org/packages/vocab#' );

		$emitter->about( 'extstaff:Sue' )
			->say( 'exterms:publication' )->is( $label1 = $emitter->blank() );
		$emitter->about( $label1 )
			->say( 'exterms:title' )->is( 'Antology of Time' );

		$emitter->about( 'extstaff:Jack' )
			->say( 'exterms:publication' )->is( $label2 = $emitter->blank() );
		$emitter->about( $label2 )
			->say( 'exterms:title' )->is( 'Enthony of Time' );

		$rdf = $emitter->drain();
		$this->assertOutputLines( 'NumberedBlankNode', $rdf );
	}

}
