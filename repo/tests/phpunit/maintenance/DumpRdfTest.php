<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Maintenance;

use DataValues\StringValue;
use MediaWikiIntegrationTestCase;
use TestSites;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\DataModel\Services\EntityId\InMemoryEntityIdPager;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Maintenance\DumpRdf;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/dumpRdf.php';

/**
 * @covers \Wikibase\Repo\Maintenance\DumpRdf
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class DumpRdfTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Needed because SiteLinksRdfBuilder is constructed from global state
		// in WikibaseRepo.entitytypes.php.
		TestSites::insertIntoDb();
	}

	private function getDumpRdf(
		array $existingEntityTypes,
		array $entityTypesWithoutRdfOutput
	): DumpRdf {
		$dumpScript = new DumpRdf();

		$mockRepo = new MockRepository();
		$mockEntityIdPager = new InMemoryEntityIdPager();

		$snakList = new SnakList();
		$snakList->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ) );
		$snakList->addSnak( new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'stringVal' ) ) );
		/** @var EntityDocument[] $testEntities */
		$testEntities = [
			new Item( new ItemId( 'Q1' ) ),
			new Property( new NumericPropertyId( 'P1' ), null, 'string' ),
			new Property(
				new NumericPropertyId( 'P12' ),
				null,
				'string',
				new StatementList(
					new Statement(
						// P999 is non existent thus the datatype will not be present
						new PropertySomeValueSnak( new NumericPropertyId( 'P999' ) ),
						null,
						null,
						'GUID1'
					)
				)
			),
			new Item(
				new ItemId( 'Q2' ),
				new Fingerprint(
					new TermList( [
						new Term( 'en', 'en-label' ),
						new Term( 'de', 'de-label' ),
					] ),
					new TermList( [
						new Term( 'fr', 'en-desc' ),
						new Term( 'de', 'de-desc' ),
					] ),
					new AliasGroupList( [
						new AliasGroup( 'en', [ 'ali1', 'ali2' ] ),
						new AliasGroup( 'dv', [ 'ali11', 'ali22' ] ),
					] )
				),
				new SiteLinkList( [
					new SiteLink( 'enwiki', 'Berlin' ),
					new SiteLink( 'dewiki', 'England', [ new ItemId( 'Q1' ) ] ),
				] ),
				new StatementList(
					new Statement(
						new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ),
						null,
						null,
						'GUID1'
					),
					new Statement(
						new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ),
						$snakList,
						new ReferenceList( [
							new Reference( [
								new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'refSnakVal' ) ),
								new PropertyNoValueSnak( new NumericPropertyId( 'P12' ) ),
							] ),
						] ),
						'GUID2'
					)
				)
			),
			new Item(
				new ItemId( 'Q4' ),
				null,
				new SiteLinkList( [
					new SiteLink( 'enwiki', 'Category:San Jose' ),
					new SiteLink( 'dewiki', 'USA' ),
				] ),
				null
			),
		];

		foreach ( $testEntities as $key => $testEntity ) {
			$mockRepo->putEntity( $testEntity, $key, '20000101000000' );
			$mockEntityIdPager->addEntityId( $testEntity->getId() );
		}

		$sqlEntityIdPagerFactory = $this->createMock( SqlEntityIdPagerFactory::class );
		$sqlEntityIdPagerFactory->expects( $this->once() )
			->method( 'newSqlEntityIdPager' )
			->with( array_diff( $existingEntityTypes, $entityTypesWithoutRdfOutput ), EntityIdPager::INCLUDE_REDIRECTS )
			->willReturn( $mockEntityIdPager );

		// Note: We are testing with the actual RDF bindings, so we can check for actual RDF output.
		$this->setService( 'WikibaseRepo.PropertyDataTypeLookup', $this->getMockPropertyDataTypeLookup() );
		$dumpScript->setServices(
			$sqlEntityIdPagerFactory,
			$existingEntityTypes,
			$entityTypesWithoutRdfOutput,
			new NullEntityPrefetcher(),
			$mockRepo,
			new RdfBuilderFactory(
				new RdfVocabulary(
					[ '' => 'fooUri/' ],
					[ '' => 'acme/EntityData/' ],
					new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
					[ '' => '' ],
					[ '' => '' ]
				),
				WikibaseRepo::getEntityRdfBuilderFactory(),
				WikibaseRepo::getEntityContentFactory(),
				WikibaseRepo::getEntityStubRdfBuilderFactory(),
				$mockRepo
			)
		);

		return $dumpScript;
	}

	public function dumpParameterProvider(): iterable {
		return [
			'dump everything' => [
				[ 'item', 'property' ],
				[],
				[],
				__DIR__ . '/../data/maintenance/dumpRdf-log.txt',
				__DIR__ . '/../data/maintenance/dumpRdf-out.txt',
			],
			'dump with part-id' => [
				[ 'item', 'property' ],
				[],
				[
					'part-id' => 'blah',
				],
				__DIR__ . '/../data/maintenance/dumpRdf-log.txt',
				__DIR__ . '/../data/maintenance/dumpRdf-part-id-blah-out.txt',
			],
			'dump with no rdf output available for properties' => [
				[ 'item', 'property' ],
				[ 'property' ],
				[],
				__DIR__ . '/../data/maintenance/dumpRdf-no-rdf-for-property-log.txt',
				__DIR__ . '/../data/maintenance/dumpRdf-no-rdf-for-property-out.txt',
			],
		];
	}

	/**
	 * @dataProvider dumpParameterProvider
	 */
	public function testScript(
		array $existingEntityTypes,
		array $entityTypesWithoutRdfOutput,
		array $opts,
		string $expectedLogFile,
		string $expectedOutFile
	): void {
		$dumpScript = $this->getDumpRdf( $existingEntityTypes, $entityTypesWithoutRdfOutput );

		$logFileName = tempnam( sys_get_temp_dir(), "Wikibase-DumpRdfTest" );
		$outFileName = tempnam( sys_get_temp_dir(), "Wikibase-DumpRdfTest" );

		$opts = $opts + [ 'format' => 'n-triples', 'log' => $logFileName, 'output' => $outFileName ];
		$dumpScript->loadParamsAndArgs( null, $opts );

		$dumpScript->execute();

		$expectedLog = file_get_contents( $expectedLogFile );
		$expectedOut = explode( "\n", $this->fixLineEndings( file_get_contents( $expectedOutFile ) ) );
		sort( $expectedOut );

		$actualOut = $this->fixLineEndings( file_get_contents( $outFileName ) );
		$actualOut = preg_replace(
			'/<http:\/\/wikiba.se\/ontology#Dump> <http:\/\/schema.org\/dateModified> "[^"]+"/',
			"<http://wikiba.se/ontology#Dump> <http://schema.org/dateModified> \"2015-01-01T00:00:00Z\"",
			$actualOut
		);
		$actualOut = explode( "\n", $actualOut );
		sort( $actualOut );

		$this->assertEquals(
			$this->fixLineEndings( $expectedLog ),
			$this->fixLineEndings( file_get_contents( $logFileName ) )
		);
		$this->assertEquals( $expectedOut, $actualOut );
	}

	/**
	 * @dataProvider getRedirectModeProvider
	 */
	public function testGetRedirectMode( string $expected, bool $redirectOnly ): void {
		/** @var DumpRdf $dumpScript */
		$dumpScript = TestingAccessWrapper::newFromObject( new DumpRdf() );

		$dumpArgv = [ 0 => 'foo' ];
		if ( $redirectOnly ) {
			$dumpArgv[] = '--redirect-only';
		}

		$dumpScript->loadWithArgv( $dumpArgv );

		$this->assertSame( $expected, $dumpScript->getRedirectMode() );
	}

	public function getRedirectModeProvider(): iterable {
		return [
			[
				EntityIdPager::INCLUDE_REDIRECTS,
				false,
			],
			[
				EntityIdPager::ONLY_REDIRECTS,
				true,
			],
		];
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

	private function fixLineEndings( string $string ): string {
		return preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", $string );
	}

}
