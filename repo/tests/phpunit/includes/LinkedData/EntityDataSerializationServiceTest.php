<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\LinkedData;

use DataValues\Serializers\DataValueSerializer;
use HashSiteStore;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\RedirectRevision;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\LinkedData\EntityDataSerializationService
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseEntityData
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityDataSerializationServiceTest extends MediaWikiIntegrationTestCase {

	public const URI_BASE = 'http://acme.test/';
	private const URI_BASE_PROPS = 'http://prop.test/';
	public const URI_DATA = 'http://data.acme.test/';
	private const URI_DATA_PROPS = 'http://data.prop.test/';

	/**
	 * Returns a MockRepository. The following entities are defined:
	 *
	 * - Items Q23
	 * - Item Q42
	 * - Redirect Q2233 -> Q23
	 * - Redirect Q222333 -> Q23
	 * - Property P5 (item reference)
	 */
	private function getMockRepository(): MockRepository {
		$mockRepo = new MockRepository();

		$p5 = new Property( new NumericPropertyId( 'P5' ), null, 'wikibase-item' );
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

	private function newService(): EntityDataSerializationService {
		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturn( 'wikibase-item' );

		$entityTitleStoreLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleStoreLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				return Title::newFromTextThrow( $id->getEntityType() . ':' . $id->getSerialization() );
			} );

		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		// should also be unused since we configure no page props
		$entityContentFactory->expects( $this->never() )
			->method( 'newFromEntity' );

		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		// Note: We are testing with the actual RDF bindings. These should not change for well
		// known data types. Mocking the bindings would be nice, but is complex and not needed.
		$rdfBuilder = WikibaseRepo::getValueSnakRdfBuilderFactory();

		$rdfBuilderFactory = new RdfBuilderFactory(
			new RdfVocabulary(
				[ 'items' => self::URI_BASE, 'props' => self::URI_BASE_PROPS ],
				[ 'items' => self::URI_DATA, 'props' => self::URI_DATA_PROPS ],
				new EntitySourceDefinitions( [
					new DatabaseEntitySource(
						'items',
						'itemdb',
						[ 'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ] ],
						self::URI_BASE,
						'wd',
						'',
						'i'
					),
					new DatabaseEntitySource(
						'props',
						'propdb',
						[ 'property' => [ 'namespaceId' => 600, 'slot' => SlotRecord::MAIN ] ],
						self::URI_BASE_PROPS,
						'pro',
						'pro',
						'p'
					),
				], new SubEntityTypesMapper( [] ) ),
				[ 'items' => 'wd', 'props' => 'pro' ],
				[ 'items' => '', 'props' => 'pro' ]
			),
			WikibaseRepo::getEntityRdfBuilderFactory(),
			$entityContentFactory,
			WikibaseRepo::getEntityStubRdfBuilderFactory(),
			$this->getMockRepository()
		);

		return new EntityDataSerializationService(
			$entityTitleStoreLookup,
			$dataTypeLookup,
			new EntityDataFormatProvider(),
			$serializerFactory,
			$serializerFactory->newItemSerializer(),
			new HashSiteStore(),
			$rdfBuilderFactory,
			WikibaseRepo::getEntityIdParser()
		);
	}

	public function provideGetSerializedData(): iterable {
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
					'empty-description' => '/"descriptions"\:\{\}/',
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

			'Q42.jsonld' => [
				'jsonld', // format
				$entityRevQ42, // entityRev
				null, // redirect
				[], // incoming
				null, // flavor
				[ // output regex
					'start' => '!^\s*\{\s*"@graph": \[!s',
					'end' => '!\],\s*"@context": \{.*\}\s*\}\s*$!s',
					'label' => '!"label":\s*\{\s*"@language": "en",\s*"@value": "Label42"\s*\}!s',
					'item-ref Q2233' => '!"P5":\s*"wd:Q2233",!s',
					'redirect Q2233' => '!\{\s*"@id": "wd:Q2233",\s*"sameAs": "wd:Q23"\s*\}!s',
				],
				[],
				'application/ld+json', // expected mime
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
					'label P5' => '!<http://prop\.test/P5> '
						. '*<http://www\.w3\.org/2000/01/rdf-schema#label> *"Label5"@en *\.!s',
					'item-ref Q2233' => '!<http://acme\.test/statement/Q42-DEADBEEF> '
						. '*<http://prop\.test/prop/statement/P5> *<http://acme\.test/Q2233> *\.!s',
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

			'Q2233.jsonld' => [
				'jsonld', // format
				$entityRevQ23, // entityRev
				$entityRedirQ2233, // redirect
				[ $q2233, $q222333 ], // incoming
				null, // flavor
				[ // output regex
					'start' => '!^\s*\{\s*"@graph": \[!s',
					'end' => '!\],\s*"@context": \{.*\}\s*\}\s*$!s',
					'label Q23' => '!"label":\s*\{\s*"@language": "en",\s*"@value": "Label23"\s*\}!s',
					'redirect Q2233' => '!\{\s*"@id": "wd:Q2233",\s*"sameAs": "wd:Q23"\s*\}!s',
					'redirect Q222333' => '!\{\s*"@id": "wd:Q222333",\s*"sameAs": "wd:Q23"\s*\}!s',
				],
				[],
				'application/ld+json', // expected mime
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

			'Q23.jsonld' => [
				'jsonld', // format
				$entityRevQ23, // entityRev
				null, // redirect
				[ $q2233, $q222333 ], // incoming
				null, // flavor
				[ // output regex
					'start' => '!^\s*\{\s*"@graph": \[!s',
					'end' => '!\],\s*"@context": \{.*\}\s*\}\s*$!s',
					'label Q23' => '!"label":\s*\{\s*"@language": "en",\s*"@value": "Label23"\s*\}!s',
					'redirect Q2233' => '!\{\s*"@id": "wd:Q2233",\s*"sameAs": "wd:Q23"\s*\}!s',
					'redirect Q222333' => '!\{\s*"@id": "wd:Q222333",\s*"sameAs": "wd:Q23"\s*\}!s',
				],
				[],
				'application/ld+json', // expected mime
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
		string $format,
		EntityRevision $entityRev,
		?RedirectRevision $followedRedirect,
		array $incomingRedirects,
		?string $flavor,
		array $expectedDataExpressions,
		array $unexpectedDataExpressions,
		string $expectedMimeType
	): void {
		$this->setService( 'WikibaseRepo.PropertyDataTypeLookup', $this->getMockPropertyDataTypeLookup() );
		$inMemoryTermLookup = new InMemoryPrefetchingTermLookup();
		$p5 = new NumericPropertyId( 'P5' );
		$q23 = new ItemId( 'Q23' );
		$inMemoryTermLookup->setData( [ $this->getMockRepository()->getEntity( $p5 ) ] );
		$inMemoryTermLookup->setData( [ $this->getMockRepository()->getEntity( $q23 ) ] );
		$this->setService( 'WikibaseRepo.PrefetchingTermLookup', $inMemoryTermLookup );
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
			$this->assertMatchesRegularExpression( $expectedDataRegex, $data, "expected: $key" );
		}

		foreach ( $unexpectedDataExpressions as $key => $unexpectedDataRegex ) {
			$this->assertDoesNotMatchRegularExpression( $unexpectedDataRegex, $data, "unexpected: $key" );
		}
	}

	private function getMockPropertyDataTypeLookup(): PropertyDataTypeLookup {
		$mockDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$mockDataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( function( NumericPropertyId $id ) {
				if ( $id->getSerialization() === 'P999' ) {
					throw new PropertyDataTypeLookupException( $id );
				}
				return 'string';
			} );
		return $mockDataTypeLookup;
	}

}
