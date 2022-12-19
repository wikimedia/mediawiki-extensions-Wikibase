<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Rdf;

use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Rdf\PropertyStubRdfBuilder;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\PropertyStubRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class PropertyStubRdfBuilderTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	/**
	 * @var NTriplesRdfWriter
	 */
	private $writer;

	 /**
	  * @var RdfVocabulary
	  */
	private $vocabulary;

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData = null;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $termLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
		$this->writer = $this->getTestData()->getNTriplesWriter();
		$this->vocabulary = $this->getTestData()->getVocabulary();
		$this->dataTypeLookup = $this->getPropertyDataTypeLookup();
		$this->termLookup = new InMemoryPrefetchingTermLookup( false );
		$this->termsLanguages = $this->getContentLanguages();
	}

	private function getTestData(): RdfBuilderTestData {
		if ( $this->testData === null ) {
			$this->testData = new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/PropertyRdfBuilder'
			);
		}

		return $this->testData;
	}

	private function getPropertyDataTypeLookup(): PropertyDataTypeLookup {
		$mockDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$mockDataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( function( NumericPropertyId $id ) {
				return 'string';
			} );
		return $mockDataTypeLookup;
	}

	private function getContentLanguages(): ContentLanguages {
		$termsLanguages = $this->createMock( ContentLanguages::class );
		$termsLanguages->method( 'getLanguages' )->willReturn( [ 'en', 'de' ] );
		return $termsLanguages;
	}

	private function assertOrCreateNTriples( $dataSetName, RdfWriter $writer ): void {
		$actual = $writer->drain();

		try {
			$expected = $this->getTestData()->getNTriples( $dataSetName );
		} catch ( InvalidArgumentException $e ) {
			$this->getTestData()->putTestData( $dataSetName, $actual, '.actual' );
			$this->fail( "Data set $dataSetName not found! Created file with the current data "
							. 'using the suffix .actual' );

		}

		$this->helper->assertNTriplesEquals( $expected, $actual, "Data set $dataSetName" );
	}

	public function testAddEntityStub(): void {
		$propertyId = new NumericPropertyId( 'P2' );
		$this->termLookup->setData( [
			$this->getTestData()->getEntity( 'P2' ),
		] );
		$builder = new PropertyStubRdfBuilder(
			$this->termLookup,
			$this->dataTypeLookup,
			$this->termsLanguages,
			$this->vocabulary,
			$this->writer,
			[],
			[]
		);

		$builder->markForPrefetchingEntityStub( $propertyId );

		$builder->addEntityStub( $propertyId );

		$this->assertOrCreateNTriples( 'P2_terms', $this->writer );
	}

}
