<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Dumpers;

use HashSiteStore;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use MWException;
use Site;
use SiteLookup;
use stdClass;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Dumpers\RdfDumpGenerator;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory;
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ItemRdfBuilder;
use Wikibase\Repo\Rdf\PropertyRdfBuilder;
use Wikibase\Repo\Rdf\PropertySpecificComponentsRdfBuilder;
use Wikibase\Repo\Rdf\PropertyStubRdfBuilder;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\Rdf\TermsRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Dumpers\RdfDumpGenerator
 * @covers \Wikibase\Repo\Dumpers\DumpGenerator
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class RdfDumpGeneratorTest extends MediaWikiIntegrationTestCase {

	private const URI_BASE = 'http://acme.test/';
	private const URI_DATA = 'http://data.acme.test/';

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/RdfDumpGenerator'
			)
		);
	}

	public function getSiteLookup(): SiteLookup {
		$list = [];

		$wiki = new Site();
		$wiki->setGlobalId( 'enwiki' );
		$wiki->setLanguageCode( 'en' );
		$wiki->setLinkPath( 'http://enwiki.acme.test/$1' );
		$list['enwiki'] = $wiki;

		$wiki = new Site();
		$wiki->setGlobalId( 'ruwiki' );
		$wiki->setLanguageCode( 'ru' );
		$wiki->setLinkPath( 'http://ruwiki.acme.test/$1' );
		$list['ruwiki'] = $wiki;

		$wiki = new Site();
		$wiki->setGlobalId( 'test' );
		$wiki->setLanguageCode( 'test' );
		$wiki->setGroup( 'acmetest' );
		$wiki->setLinkPath( 'http://test.acme.test/$1' );
		$list['test'] = $wiki;

		return new HashSiteStore( $list );
	}

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

				$dataTypeLookup = $this->getPropertyDataTypeLookup();
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
			'property' => function(
				RdfVocabulary $vocabulary,
				RdfWriter $writer
			) {
				$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions();
				$labelPredicates = $entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES );
				//$this->setService( 'WikibaseRepo.PrefetchingTermLookup', $this->getTestData()->getMockRepository() );
				$prefetchingLookup = WikibaseRepo::getPrefetchingTermLookup();
				//$this->setService( 'WikibaseRepo.PropertyDataTypeLookup', $this->getTestData()->getMockRepository() );
				$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
				$termsLanguages = WikibaseRepo::getTermsLanguages();
				$dataTypes = WikibaseRepo::getDataTypeDefinitions()->getRdfDataTypes();

				return new PropertyStubRdfBuilder(
					$prefetchingLookup,
					$propertyDataLookup,
					$termsLanguages,
					$vocabulary,
					$writer,
					$dataTypes,
					$labelPredicates
				);
			},
		];
	}

	private function getPropertyDataTypeLookup(): PropertyDataTypeLookup {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P10' ), 'Wibblywobbly' ); // see phpunit/data/rdf/RdfDumpGenerator

		return $dataTypeLookup;
	}

	/**
	 * @param string $flavor
	 * @param EntityDocument[] $entities
	 * @param EntityId[] $redirects
	 *
	 * @throws MWException
	 */
	protected function newDumpGenerator( string $flavor, array $entityRevisions = [], array $redirects = [] ): RdfDumpGenerator {
		$out = fopen( 'php://output', 'w' );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );

		$entityRevisionLookup->method( 'getEntityRevision' )
			->willReturnCallback( function( EntityId $id ) use ( $entityRevisions, $redirects ) {
				$key = $id->getSerialization();

				if ( isset( $redirects[$key] ) ) {
					throw new RevisionedUnresolvedRedirectException( $id, $redirects[$key] );
				}

				return $entityRevisions[$key] ?? null;
			} );

		$siteLookup = $this->getSiteLookup();

		// Note: we test against the actual RDF bindings here, so we get actual RDF.
		$entityRdfBuilderFactory = new EntityRdfBuilderFactory( $this->getRdfBuilderFactoryCallbacks( $siteLookup ) );
		$entityStubRdfBuilderFactory = new EntityStubRdfBuilderFactory( $this->getStubRdfBuilderFactoryCallbacks() );

		$rdfBuilderFactory = new RdfBuilderFactory(
			new RdfVocabulary(
				[ 'test' => self::URI_BASE, 'foreign' => 'http://foreign.test/' ],
				[ 'test' => self::URI_DATA, 'foreign' => 'http://data.foreign.test/' ],
				new EntitySourceDefinitions( [
					new DatabaseEntitySource(
						'test',
						'testdb',
						[
							'item' => [ 'namespaceId' => 10000, 'slot' => SlotRecord::MAIN ],
							'property' => [ 'namespaceId' => 30000, 'slot' => SlotRecord::MAIN ],
						],
						self::URI_BASE,
						'wd',
						'',
						''
					),
				], new SubEntityTypesMapper( [] ) ),
				[ 'test' => 'wd', 'foreign' => 'foreign' ],
				[ 'test' => '', 'foreign' => 'foreign' ],
				[ 'test' => 'en-x-test' ]
			),
			$entityRdfBuilderFactory,
			$this->createMock( EntityContentFactory::class ),
			$entityStubRdfBuilderFactory,
			$entityRevisionLookup
		);

		return RdfDumpGenerator::createDumpGenerator(
			'ntriples',
			$out,
			$flavor,
			$entityRevisionLookup,
			new NullEntityPrefetcher(),
			null,
			$rdfBuilderFactory
		);
	}

	public function idProvider(): iterable {
		$p10 = new NumericPropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );
		$q40 = new ItemId( 'Q40' );
		$q4242 = new ItemId( 'Q4242' ); // hardcoded to be a redirect

		return [
			'full empty' => [ [], 'full-dump', 'empty' ],
			'full some entities' => [ [ $p10, $q30, $q40 ], 'full-dump', 'entities' ],
			'full redirect' => [ [ $p10, $q4242 ], 'full-dump', 'redirect' ],
			'truthy empty' => [ [], 'truthy-dump', 'empty' ],
			'truthy some entities' => [ [ $p10, $q30, $q40 ], 'truthy-dump', 'entities' ],
			'truthy redirect' => [ [ $p10, $q4242 ], 'truthy-dump', 'redirect' ],
		];
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGenerateDump( array $ids, string $flavor, string $dumpname ): void {
		$jsonTest = new JsonDumpGeneratorTest();
		$entityRevisions = $jsonTest->makeEntityRevisions( $ids );
		$redirects = [ 'Q4242' => new ItemId( 'Q42' ) ];
		$dumper = $this->newDumpGenerator( $flavor, $entityRevisions, $redirects );
		$dumper->setTimestamp( 1000000 );
		$callbackChecker = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'callback' ] )
			->getMock();
		$callbackChecker->expects( $this->atLeastOnce() )
			->method( 'callback' );
		$dumper->setBatchCallback( [ $callbackChecker, 'callback' ] );
		$jsonTest = new JsonDumpGeneratorTest();
		$pager = $jsonTest->makeIdPager( $ids );

		ob_start();
		$dumper->generateDump( $pager );
		$actual = ob_get_clean();
		$expected = $this->getTestData()->getNTriples( $flavor . '-' . $dumpname );

		$this->helper->assertNTriplesEquals( $expected, $actual );
	}

	public function testReferenceDedup(): void {
		$entityRevisions = [];

		$entityRevisions['Q7'] = new EntityRevision(
			$this->getTestData()->getEntity( 'Q7_no_prefixed_ids' ),
			12,
			'19700112134640'
		);
		$entityRevisions['Q9'] = new EntityRevision(
			$this->getTestData()->getEntity( 'Q9_no_prefixed_ids' ),
			12,
			'19700112134640'
		);

		$dumper = $this->newDumpGenerator( 'full-dump', $entityRevisions );
		$dumper->setTimestamp( 1000000 );
		$jsonTest = new JsonDumpGeneratorTest();
		$pager = $jsonTest->makeIdPager( [ new ItemId( 'Q7' ), new ItemId( 'Q9' ) ] );

		ob_start();
		$dumper->generateDump( $pager );
		$actual = ob_get_clean();
		$this->helper->assertNTriplesEqualsDataset( 'refs_no_prefixed_ids', $actual );
	}

}
