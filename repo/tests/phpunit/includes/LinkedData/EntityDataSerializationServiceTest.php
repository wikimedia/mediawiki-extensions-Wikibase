<?php

namespace Wikibase\Repo\Tests\LinkedData;

use DataValues\Serializers\DataValueSerializer;
use HashSiteStore;
use SiteList;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Lib\Store\RedirectRevision;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\LinkedData\EntityDataSerializationService
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseEntityData
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityDataSerializationServiceTest extends \MediaWikiTestCase {

	const URI_BASE = 'http://acme.test/';
	const URI_DATA = 'http://data.acme.test/';

	/**
	 * Returns a MockRepository. The following entities are defined:
	 *
	 * - Items Q23
	 * - Item Q42
	 * - Redirect Q2233 -> Q23
	 * - Redirect Q222333 -> Q23
	 * - Property P5 (item reference)
	 *
	 * @return MockRepository
	 */
	private function getMockRepository() {
		$mockRepo = new MockRepository();

		$p5 = new Property( new PropertyId( 'P5' ), null, 'wikibase-item' );
		$p5->setLabel( 'en', 'Label5' );
		$mockRepo->putEntity( $p5 );

		$q23 = new Item( new ItemId( 'Q23' ) );
		$q23->setLabel( 'en', 'Label23' );
		$mockRepo->putEntity( $q23 );

		$q2233 = new EntityRedirect( new ItemId( 'Q2233' ), new ItemId( 'Q23' ) );
		$mockRepo->putRedirect( $q2233 );

		$q222333 = new EntityRedirect( new ItemId( 'Q222333' ), new ItemId( 'Q23' ) );
		$mockRepo->putRedirect( $q222333 );

		$q42 = new Item( new ItemId( 'Q42' ) );
		$q42->setLabel( 'en', 'Label42' );

		$snak = new PropertyValueSnak( $p5->getId(), new EntityIdValue( $q2233->getEntityId() ) );
		$q42->getStatements()->addNewStatement( $snak, null, null, 'Q42$DEADBEEF' );

		$mockRepo->putEntity( $q42 );

		return $mockRepo;
	}

	private function newService() {
		$dataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'wikibase-item' ) );

		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getEntityType() . ':' . $id->getSerialization() );
			} ) );

		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		// Note: We are testing with the actual RDF bindings. These should not change for well
		// known data types. Mocking the bindings would be nice, but is complex and not needed.
		$rdfBuilder = $wikibaseRepo->getValueSnakRdfBuilderFactory();

		$service = new EntityDataSerializationService(
			$this->getMockRepository(),
			$titleLookup,
			$dataTypeLookup,
			$rdfBuilder,
			$wikibaseRepo->getEntityRdfBuilderFactory(),
			new SiteList(),
			new EntityDataFormatProvider(),
			$serializerFactory,
			$serializerFactory->newItemSerializer(),
			new HashSiteStore(),
			new RdfVocabulary( [ '' => self::URI_BASE ], self::URI_DATA )
		);

		return $service;
	}

	public function provideGetSerializedData() {
		$mockRepo = $this->getMockRepository();
		$entityRevQ42 = $mockRepo->getEntityRevision( new ItemId( 'Q42' ) );
		$entityRevQ23 = $mockRepo->getEntityRevision( new ItemId( 'Q23' ) );
		$entityRedirQ2233 = new RedirectRevision(
			new EntityRedirect( new ItemId( 'Q2233' ), new ItemId( 'Q23' ) ),
			127, '20150505000000'
		);

		$q2233 = new ItemId( 'Q2233' );
		$q222333 = new ItemId( 'Q222333' );

		return [
			'Q42.json' => [
				'json', // format
				$entityRevQ42, // entityRev
				null, // redirect
				[], // incoming
				null, // flavor
				[ // output regex
					'start' => '!^\s*\{!s',
					'end' => '!\}\s*$!s',
					'label' => '!"value"\s*:\s*"Label42"!s',
					'item-ref' => '!"numeric-id":2233!s',
				],
				[],
				'application/json', // expected mime
			],

			'Q42.rdf' => [
				'rdfxml', // format
				$entityRevQ42, // entityRev
				null, // redirect
				[], // incoming
				null, // flavor
				[ // output regex
					'start' => '!^<\?xml!s',
					'end' => '!</rdf:RDF>\s*$!s',
					'about' => '!rdf:about="http://acme.test/Q42"!s',
					'label' => '!>Label42<!s',
				],
				[],
				'application/rdf+xml', // expected mime
			],

			'Q42.ttl' => [
				'turtle', // format
				$entityRevQ42, // entityRev
				null, // redirect
				[], // incoming
				null, // flavor
				[ // output regex
					'start' => '!^\s*@prefix !s',
					'end' => '!\.\s*$!s',
					'label' => '!"Label42"@en!s',
				],
				[],
				'text/turtle', // expected mime
			],

			'Q42.nt' => [
				'ntriples', // format
				$entityRevQ42, // entityRev
				null, // redirect
				[], // incoming
				null, // flavor
				[ // output regex
					'data about' => '!<http://data\.acme\.test/Q42> *<http://schema\.org/about> '
						. '*<http://acme\.test/Q42> *\.!s',
					'label' => '!<http://acme\.test/Q42> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label42"@en *\.!s',
				],
				[],
				'application/n-triples', // expected mime
			],

			'Q42.nt?flavor=full' => [
				'ntriples', // format
				$entityRevQ42, // entityRev
				null, // redirect
				[], // incoming
				'full', // flavor
				[ // output regex
					'data about' => '!<http://data\.acme\.test/Q42> *<http://schema\.org/about> '
						. '*<http://acme\.test/Q42> *\.!s',
					'label Q42' => '!<http://acme\.test/Q42> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label42"@en *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
					'label P5' => '!<http://acme\.test/P5> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label5"@en *\.!s',
					'item-ref Q2233' => '!<http://acme\.test/statement/Q42-DEADBEEF> '
						. '*<http://acme\.test/prop/statement/P5> *<http://acme\.test/Q2233> *\.!s',
					'redirect Q2233' => '!<http://acme\.test/Q2233> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
				],
				[
					'redirect Q222333' => '!<http://acme\.test/Q222333> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
				],
				'application/n-triples', // expected mime
			],

			'Q2233.nt' => [
				'ntriples', // format
				$entityRevQ23, // entityRev
				$entityRedirQ2233, // redirect
				[ $q2233, $q222333 ], // incoming
				null, // flavor
				[ // output regex
					'data about' => '!<http://data\.acme\.test/Q23> *<http://schema\.org/about> '
						. '*<http://acme\.test/Q23> *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
					'redirect Q2233' => '!<http://acme\.test/Q2233> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
					'redirect Q222333' => '!<http://acme\.test/Q222333> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
				],
				[],
				'application/n-triples', // expected mime
			],

			'Q2233.nt?flavor=dump' => [
				'ntriples', // format
				$entityRevQ23, // entityRev
				$entityRedirQ2233, // redirect
				[ $q2233, $q222333 ], // incoming
				'dump', // flavor
				[ // output regex
					'redirect Q2233' => '!<http://acme\.test/Q2233> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
				],
				[
					'data about' => '!<http://data\.acme\.test/Q23> *<http://schema\.org/about> '
						. '*<http://acme\.test/Q23> *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
					'redirect Q222333' => '!<http://acme\.test/Q222333> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
				],
				'application/n-triples', // expected mime
			],

			'Q23.nt' => [
				'ntriples', // format
				$entityRevQ23, // entityRev
				null, // redirect
				[ $q2233, $q222333 ], // incoming
				null, // flavor
				[ // output regex
					'data about' => '!<http://data\.acme\.test/Q23> *<http://schema\.org/about> '
						. '*<http://acme\.test/Q23> *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
					'redirect Q2233' => '!<http://acme\.test/Q2233> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
					'redirect Q222333' => '!<http://acme\.test/Q222333> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
				],
				[
				],
				'application/n-triples', // expected mime
			],

			'Q23.nt?flavor=dump' => [
				'ntriples', // format
				$entityRevQ23, // entityRev
				null, // redirect
				[ $q2233, $q222333 ], // incoming
				'dump', // flavor
				[ // output regex
					'data about' => '!<http://data\.acme\.test/Q23> *<http://schema\.org/about> '
						. '*<http://acme\.test/Q23> *\.!s',
					'label Q23' => '!<http://acme\.test/Q23> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label23"@en *\.!s',
				],
				[
					'redirect Q2233' => '!<http://acme\.test/Q2233> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
					'redirect Q222333' => '!<http://acme\.test/Q222333> '
						. '*<http://www\.w3\.org/2002/07/owl#sameAs> '
						. '*<http://acme\.test/Q23> *\.!s',
				],
				'application/n-triples', // expected mime
			],
		];
	}

	/**
	 * @dataProvider provideGetSerializedData
	 */
	public function testGetSerializedData(
		$format,
		EntityRevision $entityRev,
		RedirectRevision $followedRedirect = null,
		array $incomingRedirects,
		$flavor,
		array $expectedDataExpressions,
		array $unexpectedDataExpressions,
		$expectedMimeType
	) {
		$service = $this->newService();
		list( $data, $mimeType ) = $service->getSerializedData(
			$format,
			$entityRev,
			$followedRedirect,
			$incomingRedirects,
			$flavor
		);

		$this->assertEquals( $expectedMimeType, $mimeType );

		foreach ( $expectedDataExpressions as $key => $expectedDataRegex ) {
			$this->assertRegExp( $expectedDataRegex, $data, "expected: $key" );
		}

		foreach ( $unexpectedDataExpressions as $key => $unexpectedDataRegex ) {
			$this->assertNotRegExp( $unexpectedDataRegex, $data, "unexpected: $key" );
		}
	}

}
