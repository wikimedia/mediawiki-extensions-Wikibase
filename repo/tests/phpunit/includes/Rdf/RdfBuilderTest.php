<?php

namespace Wikibase\Repo\Tests\Rdf;

use PageProps;
use SiteLookup;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityRdfBuilderFactory;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\NullEntityRdfBuilder;
use Wikibase\Rdf\PropertyRdfBuilder;
use Wikibase\Rdf\RdfBuilder;
use Wikibase\Rdf\RdfProducer;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikibase\Rdf\RdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfBuilderTest extends \MediaWikiTestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/RdfBuilder'
			)
		);

		$this->helper->setAllBlanksEqual( true );
	}

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData() {
		return $this->helper->getTestData();
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function ( EntityId $entityId ) {
				return Title::newFromText( $entityId->getSerialization() );
			} ) );

		return $entityTitleLookup;
	}

	/**
	 * Returns the mapping of entity types used in tests to callbacks instantiating EntityRdfBuilder
	 * instances, that are configured to use services configured for test purposes (e.g. SiteLookup).
	 *
	 * @see EntityTypeDefinitions::getRdfBuilderFactoryCallbacks
	 *
	 * TODO: move to RdfBuilderTestData?
	 *
	 * @param SiteLookup $siteLookup
	 *
	 * @return callable[]
	 */
	private function getRdfBuilderFactoryCallbacks( SiteLookup $siteLookup ) {
		return [
			'item' => function(
				$flavorFlags,
				RdfVocabulary $vocabulary,
				RdfWriter $writer,
				$mentionedEntityTracker,
				$dedupe
			) use ( $siteLookup ) {
				if ( $flavorFlags & RdfProducer::PRODUCE_SITELINKS ) {
					$sites = $siteLookup->getSites();
					$builder = new SiteLinksRdfBuilder( $vocabulary, $writer, $sites );
					$builder->setDedupeBag( $dedupe );
					return $builder;
				}
				return new NullEntityRdfBuilder();
			},
			'property' => function(
				$flavorFlags,
				RdfVocabulary $vocabulary,
				RdfWriter $writer
			) {
				return new PropertyRdfBuilder(
					$vocabulary,
					$writer
				);
			}
		];
	}

	/**
	 * @param int           $produce One of the RdfProducer::PRODUCE_... constants.
	 * @param DedupeBag     $dedup
	 * @param RdfVocabulary $vocabulary
	 *
	 * @return RdfBuilder
	 */
	private function newRdfBuilder(
		$produce,
		DedupeBag $dedup = null,
		RdfVocabulary $vocabulary = null
	) {
		if ( $dedup === null ) {
			$dedup = new HashDedupeBag();
		}

		$siteLookup = $this->getTestData()->getSiteLookup();

		// Note: using the actual factory here makes this an integration test!
		// FIXME: we want to inject an ExternalIdentifierRdfBuilder here somehow!
		$valueBuilderFactory = WikibaseRepo::getDefaultInstance()->getValueSnakRdfBuilderFactory();
		$entityRdfBuilderFactory = new EntityRdfBuilderFactory( $this->getRdfBuilderFactoryCallbacks( $siteLookup ) );
		$emitter = new NTriplesRdfWriter();
		$builder = new RdfBuilder(
			$siteLookup->getSites(),
			$vocabulary ?: $this->getTestData()->getVocabulary(),
			$valueBuilderFactory,
			$this->getTestData()->getMockRepository(),
			$entityRdfBuilderFactory,
			$produce,
			$emitter,
			$dedup,
			$this->getEntityTitleLookup()
		);

		$builder->startDocument();
		return $builder;
	}

	/**
	 * Load entity from JSON
	 *
	 * @param string $idString
	 *
	 * @return EntityDocument
	 */
	public function getEntityData( $idString ) {
		return $this->getTestData()->getEntity( $idString );
	}

	public function provideAddEntity() {
		$rdfTests = [
			[ 'Q1', 'Q1_info' ],
			[ 'Q2', [ 'Q2_meta', 'Q2_version', 'Q2_stub', 'Q2_aliases' ] ],
			[ 'Q3', [ 'Q3_meta', 'Q3_version', 'Q3_sitelinks' ] ],
			[ 'Q4', [ 'Q4_meta', 'Q4_version', 'Q4_statements', 'Q4_direct', 'Q4_values' ] ],
			[ 'Q5', 'Q5_badges' ],
			[ 'Q6', [ 'Q6_meta', 'Q6_version', 'Q6_statements', 'Q6_qualifiers', 'Q6_values', 'Q6_referenced' ] ],
			[ 'Q7', [ 'Q7_meta', 'Q7_version', 'Q7_statements', 'Q7_reference_refs', 'Q7_references', 'Q7_values' ] ],
			[ 'Q8', 'Q8_baddates' ],
		];

		return $rdfTests;
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $dataSetNames ) {
		$entity = $this->getEntityData( $entityName );

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL );
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2013-10-04T03:31:05Z" );

		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $builder->getRDF() );
	}

	public function provideAddEntityStub() {
		return [ [ 'Q2', 'Q2_stub' ] ];
	}

	/**
	 * @dataProvider provideAddEntityStub
	 */
	public function testAddEntityStub( $entityName, $dataSetNames ) {
		$entity = $this->getEntityData( $entityName );

		$builder = $this->newRdfBuilder(
			RdfProducer::PRODUCE_ALL_STATEMENTS |
			RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
			RdfProducer::PRODUCE_QUALIFIERS |
			RdfProducer::PRODUCE_REFERENCES |
			RdfProducer::PRODUCE_SITELINKS |
			RdfProducer::PRODUCE_VERSION_INFO |
			RdfProducer::PRODUCE_FULL_VALUES
		);
		$builder->addEntityStub( $entity );

		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $builder->getRDF() );
	}

	public function testAddEntityRedirect() {
		$builder = self::newRdfBuilder( 0 );

		$q1 = new ItemId( 'Q1' );
		$q11 = new ItemId( 'Q11' );
		$builder->addEntityRedirect( $q11, $q1 );

		$expected =
			'<http://acme.test/Q11> <http://www.w3.org/2002/07/owl#sameAs> <http://acme.test/Q1> .';
		$this->helper->assertNTriplesEquals( $expected, $builder->getRDF() );
	}

	public function getProduceOptions() {
		$produceTests = [
			[
				'Q4',
				RdfProducer::PRODUCE_ALL_STATEMENTS,
				[ 'Q4_meta', 'Q4_statements' ]
			],
			[
				'Q4',
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS,
				[ 'Q4_meta', 'Q4_direct' ]
			],
			[
				'Q6',
				RdfProducer::PRODUCE_ALL_STATEMENTS,
				[ 'Q6_meta', 'Q6_statements' ]
			],
			[
				'Q6',
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS,
				[ 'Q6_meta', 'Q6_statements', 'Q6_qualifiers' ]
			],
			[
				'Q7',
				RdfProducer::PRODUCE_ALL_STATEMENTS,
				[ 'Q7_meta', 'Q7_statements' ]
			],
			[
				'Q7',
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_REFERENCES,
				[ 'Q7_meta', 'Q7_statements', 'Q7_reference_refs', 'Q7_references' ]
			],
			[
				'Q3',
				RdfProducer::PRODUCE_SITELINKS,
				[ 'Q3_meta', 'Q3_sitelinks' ]
			],
			[
				'Q4',
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_PROPERTIES,
				[ 'Q4_meta', 'Q4_statements', 'Q4_props' ]
			],
			[
				'Q4',
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_FULL_VALUES,
				[ 'Q4_meta', 'Q4_values', 'Q4_statements' ]
			],
			[
				'Q1',
				RdfProducer::PRODUCE_VERSION_INFO,
				'Q1_info'
			],
			[
				'Q4',
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_RESOLVED_ENTITIES,
				[ 'Q4_meta', 'Q4_direct', 'Q4_referenced' ]
			],
			[
				'Q10',
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_RESOLVED_ENTITIES,
				'Q10_redirect'
			],
		];

		return $produceTests;
	}

	/**
	 * @dataProvider getProduceOptions
	 */
	public function testRdfOptions( $entityName, $produceOption, $dataSetNames ) {
		$entity = $this->getEntityData( $entityName );

		$builder = $this->newRdfBuilder( $produceOption );
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2013-10-04T03:31:05Z" );
		$builder->resolveMentionedEntities( $this->getTestData()->getMockRepository() );
		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $builder->getRDF() );
	}

	public function testDumpHeader() {
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_VERSION_INFO );
		$builder->addDumpHeader( 1426110695 );
		$dataSetNames = 'dumpheader';
		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $builder->getRDF() );
	}

	public function testDeduplication() {
		$bag = new HashDedupeBag();

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q7' ) );
		$data1 = $builder->getRDF();

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q9' ) );
		$data2 = $builder->getRDF();

		$dataSetNames = 'Q7_Q9_dedup';
		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $data1 . $data2 );
	}

	public function getProps() {
		return [
			'simple prop' => [
				'prop1',
				[
					'claims' => [ 'name' => 'rdf-claims' ]
				]
			],
			'two props' => [
				'prop2',
				[
					'claims' => [ 'name' => 'rdf-claims' ],
					'sitelinks' => [ 'name' => 'rdf-sitelinks' ]
				]
			],
			'unknown prop' => [
				'prop3',
				[
					'Xclaims' => [ 'name' => 'rdf-claims' ],
					'sitelinks' => [ 'name' => 'rdf-sitelinks' ]
				]
			],
			'types' => [
				'prop4',
				[
					'claims' => [ 'name' => 'rdf-claims', 'type' => 'integer' ],
					'sitelinks' => [ 'name' => 'rdf-sitelinks', 'type' => 'float' ]
				]
			],
		];
	}

	/**
	 * @return PageProps
	 */
	private function getPropsMock() {
		$propsMock = $this->getMockBuilder( PageProps::class )
			->disableOriginalConstructor()
			->getMock();

		$propsMock->method( 'getProperties' )
			->willReturnCallback( function ( Title $title, $propertyNames ) {
				$props = [];
				foreach ( $propertyNames as $prop ) {
					if ( $prop[0] == 'X' ) {
						continue;
					}
					$props[$prop] = "test$prop";
					// Numeric one
					$props["len$prop"] = strlen( $prop );
				}
				return [ 'fakeID' => $props ];
			} );

		return $propsMock;
	}

	/**
	 * @dataProvider getProps
	 * @param string $name Datafile name
	 * @param array $props Property config
	 */
	public function testPageProps( $name, $props ) {
		$vocab = new RdfVocabulary( [ '' => RdfBuilderTestData::URI_BASE ], RdfBuilderTestData::URI_DATA,
				[], [], $props );
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, null, $vocab );

		$builder->setPageProps( $this->getPropsMock() );

		$builder->addEntityPageProps( $this->getEntityData( 'Q9' )->getId() );
		$data = $builder->getRDF();

		$this->helper->assertNTriplesEqualsDataset( $name, $data );
	}

	public function testPagePropsNone() {
		// Props disabled by flag
		$props = [
			'claims' => [ 'name' => 'rdf-claims' ]
		];
		$vocab = new RdfVocabulary( [ '' => RdfBuilderTestData::URI_BASE ], RdfBuilderTestData::URI_DATA,
				[], [], $props );
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL & ~RdfProducer::PRODUCE_PAGE_PROPS, null, $vocab );

		$builder->setPageProps( $this->getPropsMock() );

		$builder->addEntityPageProps( $this->getEntityData( 'Q9' )->getId() );
		$data = $builder->getRDF();
		$this->assertEquals( "", $data, "Should return empty string" );

		// Props disabled by config of vocabulary
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL );

		$builder->setPageProps( $this->getPropsMock() );

		$builder->addEntityPageProps( $this->getEntityData( 'Q9' )->getId() );
		$data = $builder->getRDF();
		$this->assertEquals( "", $data, "Should return empty string" );
	}

}
