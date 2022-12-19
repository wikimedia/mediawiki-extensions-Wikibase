<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Rdf;

use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Rdf\ItemStubRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\TermsRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class ItemStubRdfBuilderIntegrationTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;
	private $termLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/TermsRdfBuilder'
			)
		);
		$this->termLookup = new InMemoryPrefetchingTermLookup();
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
	 * @param RdfWriter $writer
	 * @param string[]|null $languages
	 *
	 * @return ItemStubRdfBuilder
	 */
	private function newBuilder( RdfWriter $writer, array $languages = null ): ItemStubRdfBuilder {
		$vocabulary = $this->getTestData()->getVocabulary();

		$builder = new ItemStubRdfBuilder(
			$this->termLookup,
			$vocabulary,
			$writer,
			[],
			$languages
		);

		return $builder;
	}

	private function assertNTriples( $dataSetName, RdfWriter $writer ) {
		$actual = $writer->drain();
		$this->helper->assertNTriplesEqualsDataset( $dataSetName, $actual );
	}

	public function provideAddEntityStub() {
		return [
			[ 'Q2', 'Q2_terms_stubs', [ 'en', 'ru' ] ],
			[ 'Q2', 'Q2_terms_stubs_ru', [ 'ru' ] ],
		];
	}

	/**
	 * @dataProvider provideAddEntityStub
	 */
	public function testAddEntityStub( $entityName, $dataSetName, array $languages = null ) {
		$entityId = new ItemId( $entityName );

		$this->termLookup->setData( [
			$this->getTestData()->getEntity( $entityName ),
		] );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer, $languages )->addEntityStub( $entityId );

		$this->assertNTriples( $dataSetName, $writer );
	}

}
