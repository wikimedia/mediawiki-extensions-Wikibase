<?php

namespace Wikimedia\Purtle\Tests;

use InvalidArgumentException;
use Wikimedia\Purtle\RdfWriterFactory;

/**
 * @covers Wikimedia\Purtle\RdfWriterFactory
 *
 * @group Purtle
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class RdfWriterFactoryTest extends \MediaWikiTestCase {

	public function testSupportedFormats() {
		$factory = new RdfWriterFactory();

		$formats = $factory->getSupportedFormats();

		$this->assertInternalType( 'array', $formats );
		$this->assertNotEmpty( $formats );
	}

	public function testGetWriter() {
		$factory = new RdfWriterFactory();

		foreach ( $factory->getSupportedFormats() as $format ) {
			$writer = $factory->getWriter( $format );

			$this->assertInstanceOf( 'Wikimedia\Purtle\RdfWriter', $writer );
		}
	}

	public function testGivenInvalidFormat_getWriterThrowsException() {
		$factory = new RdfWriterFactory();
		$this->setExpectedException( InvalidArgumentException::class );
		$factory->getWriter( 'invalid' );
	}

	public function testGetFormatName() {
		$factory = new RdfWriterFactory();

		foreach ( $factory->getSupportedFormats() as $format ) {
			$actual = $factory->getFormatName( $format );

			// the canonical name should just stay
			$this->assertEquals( $format, $actual );
		}
	}

	public function testGivenInvalidFormat_getFormatNameReturnsFalse() {
		$factory = new RdfWriterFactory();
		$this->assertFalse( $factory->getFormatName( 'invalid' ) );
	}

	public function provideFormats() {
		return array(
			// N3 (currently falls through to turtle)
			array( 'N3', 'n3', 'n3', 'text/n3' ),
			array( 'text/n3', 'n3', 'n3', 'text/n3' ),
			array( 'text/rdf+n3', 'n3', 'n3', 'text/n3' ),

			array( 'ttl', 'turtle', 'ttl', 'text/turtle' ),
			array( 'turtle', 'turtle', 'ttl', 'text/turtle' ),
			array( 'text/turtle', 'turtle', 'ttl', 'text/turtle' ),
			array( 'application/x-turtle', 'turtle', 'ttl', 'text/turtle' ),

			array( 'nt', 'ntriples', 'nt', 'application/n-triples' ),
			array( 'ntriples', 'ntriples', 'nt', 'application/n-triples' ),
			array( 'n-triples', 'ntriples', 'nt', 'application/n-triples' ),
			array( 'text/plain', 'ntriples', 'nt', 'application/n-triples' ),
			array( 'text/n-triples', 'ntriples', 'nt', 'application/n-triples' ),
			array( 'application/ntriples', 'ntriples', 'nt', 'application/n-triples' ),
			array( 'application/n-triples', 'ntriples', 'nt', 'application/n-triples' ),

			array( 'xml', 'rdfxml', 'rdf', 'application/rdf+xml' ),
			array( 'rdf', 'rdfxml', 'rdf', 'application/rdf+xml' ),
			array( 'rdfxml', 'rdfxml', 'rdf', 'application/rdf+xml' ),
			array( 'application/rdf+xml', 'rdfxml', 'rdf', 'application/rdf+xml' ),
			array( 'application/xml', 'rdfxml', 'rdf', 'application/rdf+xml' ),
			array( 'text/xml', 'rdfxml', 'rdf', 'application/rdf+xml' ),
		);
	}

	/**
	 * @dataProvider provideFormats
	 */
	public function testFormats( $name, $canonicalName, $expectedFileExtension, $expectedMimeType ) {
		$factory = new RdfWriterFactory();

		$this->assertEquals( $canonicalName, $factory->getFormatName( $name ) );
		$this->assertEquals( $expectedFileExtension, $factory->getFileExtension( $canonicalName ) );
		$this->assertContains( $expectedMimeType, $factory->getMimeTypes( $canonicalName ) );

		$writer = $factory->getWriter( $canonicalName );
		$this->assertInstanceOf( 'Wikimedia\Purtle\RdfWriter', $writer );
	}

	public function testGetMimeTypes() {
		$factory = new RdfWriterFactory();

		foreach ( $factory->getSupportedFormats() as $format ) {
			$mimeTypes = $factory->getMimeTypes( $format );

			$this->assertInternalType( 'array', $mimeTypes );
			$this->assertNotEmpty( $mimeTypes );
		}
	}

	public function testGivenInvalidFormat_getMimeTypesThrowsException() {
		$factory = new RdfWriterFactory();
		$this->setExpectedException( InvalidArgumentException::class );
		$factory->getMimeTypes( 'invalid' );
	}

	public function testGetFileExtensions() {
		$factory = new RdfWriterFactory();

		foreach ( $factory->getSupportedFormats() as $format ) {
			$extension = $factory->getFileExtension( $format );

			$this->assertInternalType( 'string', $extension );
		}
	}

	public function testGivenInvalidFormat_getFileExtensionsThrowsException() {
		$factory = new RdfWriterFactory();
		$this->setExpectedException( InvalidArgumentException::class );
		$factory->getFileExtension( 'invalid' );
	}

}
