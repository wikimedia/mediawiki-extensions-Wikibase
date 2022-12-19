<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Rdf;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use RequestContext;
use SiteLookup;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory;
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\HashDedupeBag;
use Wikibase\Repo\Rdf\ItemRdfBuilder;
use Wikibase\Repo\Rdf\ItemStubRdfBuilder;
use Wikibase\Repo\Rdf\PropertyRdfBuilder;
use Wikibase\Repo\Rdf\PropertySpecificComponentsRdfBuilder;
use Wikibase\Repo\Rdf\PropertyStubRdfBuilder;
use Wikibase\Repo\Rdf\RdfBuilder;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\Rdf\TermsRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Rdf\RdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfBuilderTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	/**
	 * @var WikibaseSettings
	 */
	private $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/RdfBuilder'
			)
		);

		$this->helper->setAllBlanksEqual( false );
		$this->settings = clone WikibaseRepo::getSettings();
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
	private function getRdfBuilderFactoryCallbacks( SiteLookup $siteLookup ): array {
		return [
			'item' => function(
				$flavorFlags,
				RdfVocabulary $vocabulary,
				RdfWriter $writer,
				$mentionedEntityTracker,
				$dedupe
			) use ( $siteLookup ) {
				$this->setService( 'SiteLookup', $siteLookup );
				$this->setService( 'WikibaseRepo.PropertyDataTypeLookup', $this->getTestData()->getMockRepository() );
				$services = $this->getServiceContainer();
				$sites = $services->getSiteLookup()->getSites();
				$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
				$valueSnakRdfBuilderFactory = new ValueSnakRdfBuilderFactory(
					WikibaseRepo::getDataTypeDefinitions( $services )
						->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE )
				);

				$truthyStatementRdfBuilderFactory = new TruthyStatementRdfBuilderFactory(
					$dedupe,
					$vocabulary,
					$writer,
					$valueSnakRdfBuilderFactory,
					$mentionedEntityTracker,
					$propertyDataLookup
				);
				$fullStatementRdfBuilderFactory = new FullStatementRdfBuilderFactory(
					$vocabulary,
					$writer,
					$valueSnakRdfBuilderFactory,
					$mentionedEntityTracker,
					$dedupe,
					$propertyDataLookup
				);
				$siteLinksRdfBuilder = new SiteLinksRdfBuilder( $vocabulary, $writer, $sites );
				$siteLinksRdfBuilder->setDedupeBag( $dedupe );

				$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );
				$termsRdfBuilder = new TermsRdfBuilder(
					$vocabulary,
					$writer,
					$entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES )
				);

				return new ItemRdfBuilder(
					$flavorFlags,
					$siteLinksRdfBuilder,
					$termsRdfBuilder,
					$truthyStatementRdfBuilderFactory,
					$fullStatementRdfBuilderFactory
				);
			},
			'property' => function(
				$flavorFlags,
				RdfVocabulary $vocabulary,
				RdfWriter $writer,
				$mentionedEntityTracker,
				$dedupe
			) {
				$services = MediaWikiServices::getInstance();
				$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );
				$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
				$valueSnakRdfBuilderFactory = new ValueSnakRdfBuilderFactory(
					WikibaseRepo::getDataTypeDefinitions( $services )
						->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE )
				);

				$termsRdfBuilder = new TermsRdfBuilder(
					$vocabulary,
					$writer,
					$entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES )
				);

				$truthyStatementRdfBuilderFactory = new TruthyStatementRdfBuilderFactory(
					$dedupe,
					$vocabulary,
					$writer,
					$valueSnakRdfBuilderFactory,
					$mentionedEntityTracker,
					$propertyDataLookup
				);
				$fullStatementRdfBuilderFactory = new FullStatementRdfBuilderFactory(
					$vocabulary,
					$writer,
					$valueSnakRdfBuilderFactory,
					$mentionedEntityTracker,
					$dedupe,
					$propertyDataLookup
				);

				$dataTypeLookup = WikibaseRepo::getPropertyDataTypeLookup();
				$propertySpecificRdfBuilder = new PropertySpecificComponentsRdfBuilder(
					$vocabulary,
					$writer,
					$dataTypeLookup,
					WikibaseRepo::getDataTypeDefinitions()->getRdfDataTypes()
				);
				return new PropertyRdfBuilder(
					$flavorFlags,
					$truthyStatementRdfBuilderFactory,
					$fullStatementRdfBuilderFactory,
					$termsRdfBuilder,
					$propertySpecificRdfBuilder
				);
			},
		];
	}

	/**
	 * Returns the mapping of entity types used in tests to callbacks instantiating EntityStubRdfBuilder
	 * instances.
	 *
	 * @see EntityTypeDefinitions::getStubRdfBuilderFactoryCallbacks
	 *
	 * @return callable[]
	 */
	private function getStubRdfBuilderFactoryCallbacks(): array {
		return [
			'item' => function(
				RdfVocabulary $vocabulary,
				RdfWriter $writer
			) {
				$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions();
				$labelPredicates = $entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES );
				$languageFallbackFactory = WikibaseRepo::getLanguageFallbackChainFactory();
				$languageCodes = $languageFallbackFactory->newFromContext( RequestContext::getMain() )->getFetchLanguageCodes();

				return new ItemStubRdfBuilder(
					$this->getTestData()->getMockTermLookup( false ),
					$vocabulary,
					$writer,
					$labelPredicates,
					$languageCodes
				);
			},
			'property' => function(
				RdfVocabulary $vocabulary,
				RdfWriter $writer
			) {
				$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions();
				$labelPredicates = $entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES );
				$termsLanguages = WikibaseRepo::getTermsLanguages();
				$dataTypes = WikibaseRepo::getDataTypeDefinitions()->getRdfDataTypes();

				return new PropertyStubRdfBuilder(
					$this->getTestData()->getMockTermLookup(),
					$this->getTestData()->getMockRepository(),
					$termsLanguages,
					$vocabulary,
					$writer,
					$dataTypes,
					$labelPredicates
				);
			},
		];
	}

	/**
	 * @param int           $produce One of the RdfProducer::PRODUCE_... constants.
	 * @param DedupeBag     $dedup
	 * @param RdfVocabulary $vocabulary
	 */
	private function newRdfBuilder(
		int $produce,
		DedupeBag $dedup = null,
		RdfVocabulary $vocabulary = null
	): RdfBuilder {
		if ( $dedup === null ) {
			$dedup = new HashDedupeBag();
		}

		$siteLookup = $this->getTestData()->getSiteLookup();

		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		// this default EntityContentFactory expects that page props are disabled;
		// for tests with page props, override it with TestingAccessWrapper afterwards
		$entityContentFactory->expects( $this->never() )
			->method( 'newFromEntity' );

		// Note: using the actual factory here makes this an integration test!
		// FIXME: we want to inject an ExternalIdentifierRdfBuilder here somehow!
		$entityRdfBuilderFactory = new EntityRdfBuilderFactory( $this->getRdfBuilderFactoryCallbacks( $siteLookup ) );
		$emitter = new NTriplesRdfWriter();
		$entityStubRdfBuilderFactory = new EntityStubRdfBuilderFactory( $this->getStubRdfBuilderFactoryCallbacks() );

		$entityRevisionLookup = $this->getTestData()->getMockEntityRevsionLookup();

		$builder = new RdfBuilder(
			$vocabulary ?: $this->getTestData()->getVocabulary(),
			$entityRdfBuilderFactory,
			$produce,
			$emitter,
			$dedup,
			$entityContentFactory,
			$entityStubRdfBuilderFactory,
			$entityRevisionLookup
		);

		$builder->startDocument();
		return $builder;
	}

	/**
	 * Load entity from JSON
	 */
	public function getEntityData( string $idString ): EntityDocument {
		return $this->getTestData()->getEntity( $idString );
	}

	public function provideAddEntity(): iterable {
		$rdfTests = [
			[ 'Q1', 'Q1_info' ],
			[ 'Q2', [ 'Q2_meta', 'Q2_version', 'Q2_stub', 'Q2_aliases' ] ],
			[ 'Q3', [ 'Q3_meta', 'Q3_version', 'Q3_sitelinks' ] ],
			[
				'Q4_no_prefixed_ids',
				[
					'Q4_meta',
					'Q4_version',
					'Q4_statements_foreignsource_properties',
					'Q4_direct_foreignsource_properties',
					'Q4_values_foreignsource_properties',
				],
			],
			[ 'Q5', 'Q5_badges' ],
			[
				'Q6_no_prefixed_ids',
				[
					'Q6_meta',
					'Q6_version',
					'Q6_statements_foreignsource_properties',
					'Q6_qualifiers_foreignsource_properties',
					'Q6_values_foreignsource_properties',
					'Q6_referenced_foreignsource_properties',
				],
			],
			[
				'Q7_no_prefixed_ids',
				[
					'Q7_meta',
					'Q7_version',
					'Q7_statements_foreignsource_properties',
					'Q7_reference_refs_foreignsource_properties',
					'Q7_references_foreignsource_properties',
					'Q7_values_foreignsource_properties',
				],
			],
			[ 'Q8', 'Q8_baddates_foreignsource_properties' ],
		];

		return $rdfTests;
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( string $entityName, $dataSetNames ): void {
		$entity = $this->getEntityData( $entityName );

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL );
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2013-10-04T03:31:05Z" );

		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $builder->getRDF() );
	}

	public function testAddEntityStub(): void {
		$this->setUserLang( 'de' );
		$entityId = $this->getEntityData( 'Q2' )->getId();
		$builder = $this->newRdfBuilder(
			RdfProducer::PRODUCE_ALL_STATEMENTS |
			RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
			RdfProducer::PRODUCE_QUALIFIERS |
			RdfProducer::PRODUCE_REFERENCES |
			RdfProducer::PRODUCE_SITELINKS |
			RdfProducer::PRODUCE_VERSION_INFO |
			RdfProducer::PRODUCE_FULL_VALUES
		);
		$builder->addEntityStub( $entityId );

		$this->helper->assertNTriplesEqualsDataset( [ 'Q2_stub_request_languages' ], $builder->getRDF() );
	}

	public function testAddSubEntity(): void {
		$mainEntity = $this->getEntityData( 'Q2' );
		$subEntity = $this->getEntityData( 'Q3' );

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL );
		$builder->subEntityMentioned( $subEntity );
		$builder->addEntity( $mainEntity );
		$builder->addEntityRevisionInfo( $mainEntity->getId(), 42, "2013-10-04T03:31:05Z" );
		$builder->addEntityRevisionInfo( $subEntity->getId(), 42, "2013-10-04T03:31:05Z" );

		$this->helper->assertNTriplesEqualsDataset(
			[ 'Q2_meta', 'Q2_version', 'Q2_stub', 'Q2_aliases', 'Q3_meta', 'Q3_version', 'Q3_sitelinks' ],
			$builder->getRDF()
		);
	}

	public function testAddEntityRedirect(): void {
		$builder = self::newRdfBuilder( 0 );

		$q1 = new ItemId( 'Q1' );
		$q11 = new ItemId( 'Q11' );
		$builder->addEntityRedirect( $q11, $q1 );

		$expected =
			'<http://acme.test/Q11> <http://www.w3.org/2002/07/owl#sameAs> <http://acme.test/Q1> .';
		$this->helper->assertNTriplesEquals( $expected, $builder->getRDF() );
	}

	public function getProduceOptions(): iterable {
		return [
			[
				'Q4_no_prefixed_ids',
				RdfProducer::PRODUCE_ALL_STATEMENTS,
				[ 'Q4_meta', 'Q4_statements_foreignsource_properties' ],
			],
			[
				'Q4_no_prefixed_ids',
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS,
				[ 'Q4_meta', 'Q4_direct_foreignsource_properties' ],
			],
			[
				'Q6_no_prefixed_ids',
				RdfProducer::PRODUCE_ALL_STATEMENTS,
				[ 'Q6_meta', 'Q6_statements_foreignsource_properties' ],
			],
			[
				'Q6_no_prefixed_ids',
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS,
				[ 'Q6_meta', 'Q6_statements_foreignsource_properties', 'Q6_qualifiers_foreignsource_properties' ],
			],
			[
				'Q7_no_prefixed_ids',
				RdfProducer::PRODUCE_ALL_STATEMENTS,
				[ 'Q7_meta', 'Q7_statements_foreignsource_properties' ],
			],
			[
				'Q7_no_prefixed_ids',
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_REFERENCES,
				[
					'Q7_meta',
					'Q7_statements_foreignsource_properties',
					'Q7_reference_refs_foreignsource_properties',
					'Q7_references_foreignsource_properties',
				],
			],
			[
				'Q3',
				RdfProducer::PRODUCE_SITELINKS,
				[ 'Q3_meta', 'Q3_sitelinks' ],
			],
			[
				'Q4_no_prefixed_ids',
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_PROPERTIES,
				[ 'Q4_meta', 'Q4_statements_foreignsource_properties', 'Q4_props_foreignsource_properties' ],
			],
			[
				'Q4_no_prefixed_ids',
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_FULL_VALUES,
				[ 'Q4_meta', 'Q4_values_foreignsource_properties', 'Q4_statements_foreignsource_properties' ],
			],
			[
				'Q1',
				RdfProducer::PRODUCE_VERSION_INFO,
				'Q1_info',
			],
			[
				'Q4_no_prefixed_ids',
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_RESOLVED_ENTITIES,
				[ 'Q4_meta', 'Q4_direct_foreignsource_properties', 'Q4_referenced' ],
			],
			"q10" => [
				'Q10',
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_RESOLVED_ENTITIES,
				'Q10_redirect_foreignsource_properties',
			],
		];
	}

	/**
	 * @dataProvider getProduceOptions
	 */
	public function testRdfOptions( string $entityName, int $produceOption, $dataSetNames ): void {
		$entity = $this->getEntityData( $entityName );
		$builder = $this->newRdfBuilder( $produceOption );
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2013-10-04T03:31:05Z" );
		$builder->resolveMentionedEntities( $this->getTestData()->getMockRepository() );
		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $builder->getRDF() );
	}

	public function testDumpHeader(): void {
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_VERSION_INFO );
		$builder->addDumpHeader( 1426110695 );
		$dataSetNames = 'dumpheader';
		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $builder->getRDF() );
	}

	public function testDeduplication(): void {
		$bag = new HashDedupeBag();

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q7_no_prefixed_ids' ) );
		$data1 = $builder->getRDF();

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q9_no_prefixed_ids' ) );
		$data2 = $builder->getRDF();

		$this->helper->assertNTriplesEqualsDataset( 'Q7_Q9_dedup_foreignsource_properties', $data1 . $data2 );
	}

	public function getProps(): iterable {
		return [
			'simple prop' => [
				'prop1',
				[
					'claims' => [ 'name' => 'rdf-claims' ],
				],
			],
			'two props' => [
				'prop2',
				[
					'claims' => [ 'name' => 'rdf-claims' ],
					'sitelinks' => [ 'name' => 'rdf-sitelinks' ],
				],
			],
			'unknown prop' => [
				'prop3',
				[
					'Xclaims' => [ 'name' => 'rdf-claims' ],
					'sitelinks' => [ 'name' => 'rdf-sitelinks' ],
				],
			],
			'types' => [
				'prop4',
				[
					'claims' => [ 'name' => 'rdf-claims', 'type' => 'integer' ],
					'sitelinks' => [ 'name' => 'rdf-sitelinks', 'type' => 'float' ],
				],
			],
		];
	}

	private function getContentFactoryMock(): EntityContentFactory {
		$contentFactoryMock = $this->createMock( EntityContentFactory::class );
		$contentFactoryMock->method( 'newFromEntity' )
			->willReturnCallback( function ( EntityDocument $entity ): EntityContent {
				$contentMock = $this->createMock( EntityContent::class );
				$contentMock->method( 'getEntityPageProperties' )
					->willReturn( [
						'claims' => 'testclaims',
						'lenclaims' => strlen( 'claims' ),
						'sitelinks' => 'testsitelinks',
						'lensitelinks' => strlen( 'sitelinks' ),
					] );
				return $contentMock;
			} );
		return $contentFactoryMock;
	}

	/**
	 * @dataProvider getProps
	 * @param string $name Datafile name
	 * @param array $props Property config
	 */
	public function testPageProps( string $name, array $props ): void {
		$vocab = new RdfVocabulary(
			[ '' => RdfBuilderTestData::URI_BASE ],
			[ '' => RdfBuilderTestData::URI_DATA ],
			new EntitySourceDefinitions(
				[ new DatabaseEntitySource(
					'',
					'somedb',
					[ 'item' => [ 'namespaceId' => 123, 'slot' => SlotRecord::MAIN ] ],
					'',
					'',
					'',
					''
				) ],
				new SubEntityTypesMapper( [] )
			),
			[ '' => '' ],
			[ '' => '' ],
			[],
			[],
			$props,
			'http://creativecommons.org/publicdomain/zero/1.0/'
		);
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, null, $vocab );

		TestingAccessWrapper::newFromObject( $builder )->entityContentFactory
			= $this->getContentFactoryMock();

		$builder->addEntityPageProps( $this->getEntityData( 'Q9' ) );
		$data = $builder->getRDF();

		$this->helper->assertNTriplesEqualsDataset( $name, $data );
	}

	public function testPagePropsNone(): void {
		// Props disabled by flag
		$props = [
			'claims' => [ 'name' => 'rdf-claims' ],
		];
		$vocab = new RdfVocabulary(
			[ '' => RdfBuilderTestData::URI_BASE ],
			[ '' => RdfBuilderTestData::URI_DATA ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => '' ],
			[ '' => '' ],
			[],
			[],
			$props,
			'http://creativecommons.org/publicdomain/zero/1.0/'
		);
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL & ~RdfProducer::PRODUCE_PAGE_PROPS, null, $vocab );

		$builder->addEntityPageProps( $this->getEntityData( 'Q9' ) );
		$data = $builder->getRDF();
		$this->assertSame( "", $data, "Should return empty string" );

		// Props disabled by config of vocabulary
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL );

		$builder->addEntityPageProps( $this->getEntityData( 'Q9' ) );
		$data = $builder->getRDF();
		$this->assertSame( "", $data, "Should return empty string" );
	}

}
