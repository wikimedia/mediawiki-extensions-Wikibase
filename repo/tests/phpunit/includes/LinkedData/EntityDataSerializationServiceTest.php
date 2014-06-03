<?php

namespace Wikibase\Test;

use SiteList;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;

/**
 * @covers Wikibase\Repo\LinkedData\EntityDataSerializationService
 *
 * @group Wikibase
 * @group WikibaseEntityData
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityDataSerializationServiceTest extends \PHPUnit_Framework_TestCase {

	const URI_BASE = 'http://acme.test/';
	const URI_DATA = 'http://data.acme.test/';

	private function newService() {
		$entityLookup = new MockRepository();

		$dataTypeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'string' ) );

		$titleLookup = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getEntityType() . ':' . $id->getSerialization() );
			} ) );

		$serializerOptions = new SerializationOptions();
		$serializerFactory = new SerializerFactory( $serializerOptions, $dataTypeLookup );

		$service = new EntityDataSerializationService(
			self::URI_BASE,
			self::URI_DATA,
			$entityLookup,
			$titleLookup,
			$serializerFactory,
			new SiteList()
		);

		$service->setFormatWhiteList(
			array(
				// using the API
				'json', // default
				'php',
				'xml',

				// using easyRdf
				'rdfxml',
				'n3',
				'turtle',
				'ntriples',
			)
		);

		return $service;
	}

	public static function provideGetSerializedData() {
		$cases = EntityDataTestProvider::provideGetSerializedData();

		return $cases;
	}

	/**
	 * @dataProvider provideGetSerializedData
	 */
	public function testGetSerializedData(
		$format,
		EntityRevision $entityRev,
		$expectedDataRegex,
		$expectedMimeType
	) {
		$service = $this->newService();
		list( $data, $mimeType ) = $service->getSerializedData( $format, $entityRev );

		$this->assertEquals( $expectedMimeType, $mimeType );
		$this->assertRegExp( $expectedDataRegex, $data, "outpout" );
	}

	private static $apiMimeTypes = array(
		'application/vnd.php.serialized',
		'application/json',
		'text/xml'
	);

	private static $apiExtensions = array(
		'php',
		'json',
		'xml'
	);

	private static $apiFormats = array(
		'php',
		'json',
		'xml'
	);

	private static $rdfMimeTypes = array(
		'application/rdf+xml',
		'text/n3',
		'text/rdf+n3',
		'text/turtle',
		'application/turtle',
		'application/ntriples',
	);

	private static $rdfExtensions = array(
		'rdf',
		'n3',
		'ttl',
		'nt'
	);

	private static $rdfFormats = array(
		'rdfxml',
		'n3',
		'turtle',
		'ntriples'
	);

	private static $badMimeTypes = array(
		'text/html',
		'text/text',
		// 'text/plain', // ntriples presents as text/plain!
	);

	private static $badExtensions = array(
		'html',
		'text',
		'txt',
	);

	private static $badFormats = array(
		'html',
		'text',
	);

	public function testGetSupportedMimeTypes() {
		$service = $this->newService();

		$types = $service->getSupportedMimeTypes();

		foreach ( self::$apiMimeTypes as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		foreach ( self::$rdfMimeTypes as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		foreach ( self::$badMimeTypes as $type ) {
			$this->assertFalse( in_array( $type, $types), $type );
		}
	}

	public function testGetSupportedExtensions() {
		$service = $this->newService();

		$types = $service->getSupportedExtensions();

		foreach ( self::$apiExtensions as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		foreach ( self::$rdfExtensions as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		foreach ( self::$badExtensions as $type ) {
			$this->assertFalse( in_array( $type, $types), $type );
		}
	}

	public function testGetSupportedFormats() {
		$service = $this->newService();

		$types = $service->getSupportedFormats();

		foreach ( self::$apiFormats as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		foreach ( self::$rdfFormats as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		foreach ( self::$badFormats as $type ) {
			$this->assertFalse( in_array( $type, $types), $type );
		}
	}

	public function testGetFormatName() {
		$service = $this->newService();

		$types = $service->getSupportedMimeTypes();

		foreach ( $types as $type ) {
			$format = $service->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}

		$types = $service->getSupportedExtensions();

		foreach ( $types as $type ) {
			$format = $service->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}

		$types = $service->getSupportedFormats();

		foreach ( $types as $type ) {
			$format = $service->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}
	}

	public function testGetExtension() {
		$service = $this->newService();

		$extensions = $service->getSupportedExtensions();
		foreach ( $extensions as $expected ) {
			$format = $service->getFormatName( $expected );
			$actual = $service->getExtension( $format );

			$this->assertInternalType( 'string', $actual, $expected );
		}

		foreach ( self::$badFormats as $format ) {
			$actual = $service->getExtension( $format );

			$this->assertNull( $actual, $format );
		}
	}

	public function testGetMimeType() {
		$service = $this->newService();

		$extensions = $service->getSupportedMimeTypes();
		foreach ( $extensions as $expected ) {
			$format = $service->getFormatName( $expected );
			$actual = $service->getMimeType( $format );

			$this->assertInternalType( 'string', $actual, $expected );
		}

		foreach ( self::$badFormats as $format ) {
			$actual = $service->getMimeType( $format );

			$this->assertNull( $actual, $format );
		}
	}
}
