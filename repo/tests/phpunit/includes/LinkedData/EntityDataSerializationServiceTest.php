<?php

namespace Wikibase\Test;

use SiteList;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\EntityRevision;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRedirect;
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

	/**
	 * Returns a MockRepository. The following entities are defined:
	 *
	 * - Items Q23
	 * - Item Q42
	 * - Redirect Q2233 -> Q23
	 * - Property P5 (item reference)
	 *
	 * @return MockRepository
	 */
	private function getMockRepository() {
		$mockRepo = new MockRepository();

		$p5 = new Property( new PropertyId( 'P5' ), null, 'wikibase-item' );
		$p5->getFingerprint()->setLabel( 'en', 'Label5' );
		$mockRepo->putEntity( $p5 );

		$q23 = new Item( new ItemId( 'Q23' ) );
		$q23->getFingerprint()->setLabel( 'en', 'Label23' );
		$mockRepo->putEntity( $q23 );

		$q2233 = new EntityRedirect( new ItemId( 'Q2233' ), new ItemId( 'Q23' ) );
		$mockRepo->putRedirect( $q2233 );

		$q42 = new Item( new ItemId( 'Q42' ) );
		$q42->getFingerprint()->setLabel( 'en', 'Label42' );

		$snak = new PropertyValueSnak( $p5->getId(), new EntityIdValue( $q2233->getEntityId() ) );
		$q42->getStatements()->addNewStatement( $snak, null, null, 'Q42$DEADBEEF' );

		$mockRepo->putEntity( $q42 );

		return $mockRepo;
	}

	private function newService( EntityLookup $entityLookup = null ) {
		if ( !$entityLookup ) {
			$entityLookup = $this->getMockRepository();
		}

		$dataTypeLookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'string' ) );

		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getEntityType() . ':' . $id->getSerialization() );
			} ) );
		$dataTypeLookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'string' ) );

		$serializerOptions = new SerializationOptions();
		$serializerFactory = new SerializerFactory( $serializerOptions, $dataTypeLookup );

		$service = new EntityDataSerializationService(
			self::URI_BASE,
			self::URI_DATA,
			$entityLookup,
			$titleLookup,
			$serializerFactory,
			$dataTypeLookup,
			new SiteList()
		);

		$service->setFormatWhiteList(
			array(
				// using the API
				'json', // default
				'php',

				// using RdfWriter
				'rdfxml',
				'n3',
				'turtle',
				'ntriples',
			)
		);

		return $service;
	}

	public function provideGetSerializedData() {
		$mockRepo = $this->getMockRepository();
		$entityRevQ42 = $mockRepo->getEntityRevision( new ItemId( 'Q42' ) );
		$entityRevQ23 = $mockRepo->getEntityRevision( new ItemId( 'Q23' ) );
		$entityRedirQ2233 = new EntityRedirect( new ItemId( 'Q2233' ), new ItemId( 'Q23' ) );

		return array(
			'Q42.json' => array(
				'json', // format
				$entityRevQ42, // entityRev
				null, // redirect
				null, // flavor
				array( // output regex
					'start' => '!^\s*\{!s',
					'end' => '!\}\s*$!s',
					'label' => '!"value"\s*:\s*"Label42"!s',
					'item-ref' => '!"numeric-id":2233!s',
				),
				array(),
				'application/json', // expected mime
			),

			'Q42.rdf' => array(
				'rdfxml', // format
				$entityRevQ42, // entityRev
				null, // redirect
				null, // flavor
				array( // output regex
					'start' => '!^<\?xml!s',
					'end' => '!</rdf:RDF>\s*$!s',
					'about' => '!rdf:about="http://acme.test/Q42"!s',
					'label' => '!>Label42<!s',
				),
				array(),
				'application/rdf+xml', // expected mime
			),

			'Q42.ttl' => array(
				'turtle', // format
				$entityRevQ42, // entityRev
				null, // redirect
				null, // flavor
				array( // output regex
					'start' => '!^\s*@prefix !s',
					'end' => '!\.\s*$!s',
					'label' => '!"Label42"@en!s',
				),
				array(),
				'text/turtle', // expected mime
			),

			'Q42.nt' => array(
				'ntriples', // format
				$entityRevQ42, // entityRev
				null, // redirect
				null, // flavor
				array( // output regex
					'data about' => '!<http://data\.acme\.test/Q42> *<http://schema\.org/about> *<http://acme\.test/Q42> *\.!s',
					'label' => '!<http://acme\.test/Q42> *<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label42"@en *\.!s',
				),
				array(),
				'application/n-triples', // expected mime
			),

			'Q42.nt?flavor=full' => array(
				'ntriples', // format
				$entityRevQ42, // entityRev
				null, // redirect
				'full', // flavor
				array( // output regex
					'data about' => '!<http://data\.acme\.test/Q42> *<http://schema\.org/about> *<http://acme\.test/Q42> *\.!s',
					'label Q42' => '!<http://acme\.test/Q42> *<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label42"@en *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> *<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
					'label P5' => '!<http://acme\.test/P5> *<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label5"@en *\.!s',
					'item-ref Q2233' => '!<http://acme\.test/statement/Q42-DEADBEEF> *<http://acme\.test/prop/statement/P5> *<http://acme\.test/Q2233> *\.!s',
					'redirect Q2233' => '!<http://acme\.test/Q2233> *<http://www\.w3\.org/2002/07/owl#sameAs> *<http://acme\.test/Q23> *\.!s',
				),
				array(),
				'application/n-triples', // expected mime
			),

			'Q2233.nt' => array(
				'ntriples', // format
				$entityRevQ23, // entityRev
				$entityRedirQ2233, // redirect
				null, // flavor
				array( // output regex
					'data about' => '!<http://data\.acme\.test/Q23> *<http://schema\.org/about> *<http://acme\.test/Q23> *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> *<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
					'redirect Q2233' => '!<http://acme\.test/Q2233> *<http://www\.w3\.org/2002/07/owl#sameAs> *<http://acme\.test/Q23> *\.!s',
				),
				array(),
				'application/n-triples', // expected mime
			),

			'Q2233.nt?flavor=dump' => array(
				'ntriples', // format
				$entityRevQ23, // entityRev
				$entityRedirQ2233, // redirect
				'dump', // flavor
				array( // output regex
					'redirect Q2233' => '!<http://acme\.test/Q2233> *<http://www\.w3\.org/2002/07/owl#sameAs> *<http://acme\.test/Q23> *\.!s',
					'data about' => '!<http://data\.acme\.test/Q23> *<http://schema\.org/about> *<http://acme\.test/Q23> *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> *<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
				),
				array(),
				'application/n-triples', // expected mime
			),

			'Q23.nt' => array(
				'ntriples', // format
				$entityRevQ23, // entityRev
				null, // redirect
				null, // flavor
				array( // output regex
					'data about' => '!<http://data\.acme\.test/Q23> *<http://schema\.org/about> *<http://acme\.test/Q23> *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> *<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
				),
				array(
					//TODO: once we support inclusion of incoming redirects, this check should be moved into the list of expected patterns above.
					'redirect Q2233' => '!<http://acme\.test/Q2233> *<http://www\.w3\.org/2002/07/owl#sameAs> *<http://acme\.test/Q23> *\.!s',
				),
				'application/n-triples', // expected mime
			),
		);
	}

	/**
	 * @dataProvider provideGetSerializedData
	 */
	public function testGetSerializedData(
		$format,
		EntityRevision $entityRev,
		EntityRedirect $redirect = null,
		$flavor,
		array $expectedDataExpressions,
		array $unexpectedDataExpressions,
		$expectedMimeType
	) {
		$service = $this->newService();
		list( $data, $mimeType ) = $service->getSerializedData( $format, $entityRev, $redirect, $flavor );

		$this->assertEquals( $expectedMimeType, $mimeType );

		foreach ( $expectedDataExpressions as $key => $expectedDataRegex ) {
			$this->assertRegExp( $expectedDataRegex, $data, "expected: $key" );
		}

		foreach ( $unexpectedDataExpressions as $key => $unexpectedDataRegex ) {
			$this->assertNotRegExp( $unexpectedDataRegex, $data, "unexpected: $key" );
		}
	}

	private static $apiMimeTypes = array(
		'application/vnd.php.serialized',
		'application/json',
	);

	private static $apiExtensions = array(
		'php',
		'json',
	);

	private static $apiFormats = array(
		'php',
		'json',
	);

	private static $rdfMimeTypes = array(
		'application/rdf+xml',
		'text/n3',
		'text/rdf+n3',
		'text/turtle',
		'application/x-turtle',
		'text/n-triples',
		'application/n-triples',
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
		'xml',
	);

	private static $formatMappings = array(
		'json' => 'json', // should be api json
		'application/json' => 'json', // should be api json
		'application/rdf+xml' => 'rdfxml', // should be rdfxml
		'text/n-triples' => 'ntriples', // should be ntriples
		'text/plain' => 'ntriples', // should be ntriples
		'ttl' => 'turtle', // should be turtle
	);

	public function testGetSupportedMimeTypes() {
		$service = $this->newService();

		$types = $service->getSupportedMimeTypes();

		foreach ( self::$apiMimeTypes as $type ) {
			$this->assertTrue( in_array( $type, $types), $type, "api mime type $type" );
		}

		foreach ( self::$rdfMimeTypes as $type ) {
			$this->assertTrue( in_array( $type, $types), $type, "rdf mime type $type" );
		}

		foreach ( self::$badMimeTypes as $type ) {
			$this->assertFalse( in_array( $type, $types), $type, "bad mime type $type" );
		}
	}

	public function testGetSupportedExtensions() {
		$service = $this->newService();

		$types = $service->getSupportedExtensions();

		foreach ( self::$apiExtensions as $type ) {
			$this->assertTrue( in_array( $type, $types), $type, "api extension $type" );
		}

		foreach ( self::$rdfExtensions as $type ) {
			$this->assertTrue( in_array( $type, $types), $type, "rdf extension $type" );
		}

		foreach ( self::$badExtensions as $type ) {
			$this->assertFalse( in_array( $type, $types), $type, "bad extension $type" );
		}
	}

	public function testGetSupportedFormats() {
		$service = $this->newService();

		$types = $service->getSupportedFormats();

		foreach ( self::$apiFormats as $type ) {
			$this->assertTrue( in_array( $type, $types), $type, "api format $type" );
		}

		foreach ( self::$rdfFormats as $type ) {
			$this->assertTrue( in_array( $type, $types), $type, "rdf format $type" );
		}

		foreach ( self::$badFormats as $type ) {
			$this->assertFalse( in_array( $type, $types), $type, "bad format $type" );
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

		foreach ( self::$formatMappings as $type => $expectedName ) {
			$name = $service->getFormatName( $type );
			$this->assertEquals( $expectedName, $name, $type );
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
