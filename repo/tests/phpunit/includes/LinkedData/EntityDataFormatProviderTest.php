<?php

namespace Wikibase\Repo\Tests\LinkedData;

use MediaWikiIntegrationTestCase;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;

/**
 * @covers \Wikibase\Repo\LinkedData\EntityDataFormatProvider
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseEntityData
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class EntityDataFormatProviderTest extends MediaWikiIntegrationTestCase {

	private static $apiMimeTypes = [
		'application/vnd.php.serialized',
		'application/json',
	];

	private static $apiExtensions = [
		'php',
		'json',
	];

	private static $apiFormats = [
		'php',
		'json',
	];

	private static $rdfMimeTypes = [
		'application/rdf+xml',
		'text/n3',
		'text/rdf+n3',
		'text/turtle',
		'application/x-turtle',
		'text/n-triples',
		'application/n-triples',
		'application/ld+json',
	];

	private static $rdfExtensions = [
		'rdf',
		'n3',
		'ttl',
		'nt',
		'jsonld',
	];

	private static $rdfFormats = [
		'rdfxml',
		'n3',
		'turtle',
		'ntriples',
		'jsonld',
	];

	private static $badMimeTypes = [
		'text/html',
		'text/text',
		// 'text/plain', // ntriples presents as text/plain!
	];

	private static $badExtensions = [
		'html',
		'text',
		'txt',
	];

	private static $badFormats = [
		'html',
		'text',
		'xml',
	];

	private static $formatMappings = [
		'json' => 'json', // should be api json
		'application/json' => 'json', // should be api json
		'application/rdf+xml' => 'rdfxml', // should be rdfxml
		'text/n-triples' => 'ntriples', // should be ntriples
		'text/plain' => 'ntriples', // should be ntriples
		'ttl' => 'turtle', // should be turtle
		'application/ld+json' => 'jsonld',
	];

	/**
	 * @return EntityDataFormatProvider
	 */
	private function getProvider() {
		$provider = new EntityDataFormatProvider();

		$provider->setAllowedFormats(
			[
				// using the API
				'json', // default
				'php',

				// using purtle
				'rdfxml',
				'n3',
				'turtle',
				'ntriples',
				'jsonld',
			]
		);

		return $provider;
	}

	public function testGetSupportedMimeTypes() {
		$provider = $this->getProvider();

		$types = $provider->getSupportedMimeTypes();

		foreach ( self::$apiMimeTypes as $type ) {
			$this->assertContains( $type, $types, "api mime type $type" );
		}

		foreach ( self::$rdfMimeTypes as $type ) {
			$this->assertContains( $type, $types, "rdf mime type $type" );
		}

		foreach ( self::$badMimeTypes as $type ) {
			$this->assertNotContains( $type, $types, "bad mime type $type" );
		}
	}

	public function testGetSupportedExtensions() {
		$provider = $this->getProvider();

		$types = $provider->getSupportedExtensions();

		foreach ( self::$apiExtensions as $type ) {
			$this->assertContains( $type, $types, "api extension $type" );
		}

		foreach ( self::$rdfExtensions as $type ) {
			$this->assertContains( $type, $types, "rdf extension $type" );
		}

		foreach ( self::$badExtensions as $type ) {
			$this->assertNotContains( $type, $types, "bad extension $type" );
		}
	}

	public function testGetSupportedFormats() {
		$provider = $this->getProvider();

		$types = $provider->getSupportedFormats();

		foreach ( self::$apiFormats as $type ) {
			$this->assertContains( $type, $types, "api format $type" );
		}

		foreach ( self::$rdfFormats as $type ) {
			$this->assertContains( $type, $types, "rdf format $type" );
		}

		foreach ( self::$badFormats as $type ) {
			$this->assertNotContains( $type, $types, "bad format $type" );
		}
	}

	public function testGetFormatName() {
		$provider = $this->getProvider();

		$types = $provider->getSupportedMimeTypes();

		foreach ( $types as $type ) {
			$format = $provider->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}

		$types = $provider->getSupportedExtensions();

		foreach ( $types as $type ) {
			$format = $provider->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}

		$types = $provider->getSupportedFormats();

		foreach ( $types as $type ) {
			$format = $provider->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}

		foreach ( self::$formatMappings as $type => $expectedName ) {
			$name = $provider->getFormatName( $type );
			$this->assertEquals( $expectedName, $name, $type );
		}
	}

	public function testGetExtension() {
		$provider = $this->getProvider();

		$extensions = $provider->getSupportedExtensions();
		foreach ( $extensions as $expected ) {
			$format = $provider->getFormatName( $expected );
			$actual = $provider->getExtension( $format );

			$this->assertIsString( $actual, $expected );
		}

		foreach ( self::$badFormats as $format ) {
			$actual = $provider->getExtension( $format );

			$this->assertNull( $actual, $format );
		}
	}

	public function testGetMimeType() {
		$provider = $this->getProvider();

		$extensions = $provider->getSupportedMimeTypes();
		foreach ( $extensions as $expected ) {
			$format = $provider->getFormatName( $expected );
			$actual = $provider->getMimeType( $format );

			$this->assertIsString( $actual, $expected );
		}

		foreach ( self::$badFormats as $format ) {
			$actual = $provider->getMimeType( $format );

			$this->assertNull( $actual, $format );
		}
	}

	public function rfdFormatProvider() {
		return [
			[ 'rdf', true ],
			[ 'n3', true ],
			[ 'ttl', true ],
			[ 'nt', true ],
			[ 'jsonld', true ],
			[ 'rdfxml', true ],
			[ 'turtle', true ],
			[ 'ntriples', true ],
			[ 'rdfdata', false ],
			[ 'json', false ],
			[ 'xml', false ],
			[ 'html', false ],
			[ 'txt', false ],
			[ 'text', false ],
		];
	}

	/**
	 * @dataProvider rfdFormatProvider
	 */
	public function testIsRdfFormat( $format, $expected ) {
		$this->assertEquals( $expected, $this->getProvider()->isRdfFormat( $format ) );
	}

}
