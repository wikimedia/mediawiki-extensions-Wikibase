<?php

namespace Wikibase\Repo\Tests\Rdf;

use Wikibase\Repo\Rdf\NullDedupeBag;
use Wikibase\Repo\Rdf\NullEntityMentionListener;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\SnakRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\TruthyStatementRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 */
class TruthyStatementRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/RdfBuilder'
			)
		);
	}

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData() {
		return $this->helper->getTestData();
	}

	private function newBuilder( RdfVocabulary $vocabulary, RdfWriter $writer ): TruthyStatementRdfBuilder {
		// Note: using the actual factory here makes this an integration test!
		$valueBuilderFactory = WikibaseRepo::getValueSnakRdfBuilderFactory();

		$valueBuilder = $valueBuilderFactory->getValueSnakRdfBuilder(
			RdfProducer::PRODUCE_ALL & ~RdfProducer::PRODUCE_FULL_VALUES,
			$vocabulary,
			$writer,
			new NullEntityMentionListener(),
			new NullDedupeBag()
		);

		$snakBuilder = new SnakRdfBuilder(
			$vocabulary,
			$valueBuilder,
			$this->getTestData()->getMockRepository()
		);

		return new TruthyStatementRdfBuilder(
			$vocabulary,
			$writer,
			$snakBuilder
		);
	}

	private function assertOrCreateNTriples( $dataSetName, RdfWriter $writer ) {
		$actual = $writer->drain();
		$this->helper->assertNTriplesEqualsDataset( $dataSetName, $actual );
	}

	public function testAddEntity() {
		$entity = $this->getTestData()->getEntity( 'Q4' );

		$vocabulary = $this->getTestData()->getVocabulary();
		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $vocabulary, $writer )->addEntity( $entity );

		$this->assertOrCreateNTriples( [ 'Q4_direct' ], $writer );
	}

	public function testAddEntity_whenPropertiesFromOtherWikibase() {
		$entity = $this->getTestData()->getEntity( 'Q4' );

		$vocabulary = $this->getTestData()->getVocabularyForPropertiesFromOtherWikibase();
		$writer = $this->getTestData()->getNTriplesWriterForPropertiesFromOtherWikibase();
		$this->newBuilder( $vocabulary, $writer )->addEntity( $entity );

		$this->assertOrCreateNTriples( [ 'Q4_direct_foreignsource_properties' ], $writer );
	}

	public function testAddStatements() {
		$entity = $this->getTestData()->getEntity( 'Q4' );

		$vocabulary = $this->getTestData()->getVocabulary();
		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $vocabulary, $writer )->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertOrCreateNTriples( [ 'Q4_direct' ], $writer );
	}

	public function testAddStatements_whenPropertiesFromOtherWikibase() {
		$entity = $this->getTestData()->getEntity( 'Q4' );

		$vocabulary = $this->getTestData()->getVocabularyForPropertiesFromOtherWikibase();
		$writer = $this->getTestData()->getNTriplesWriterForPropertiesFromOtherWikibase();
		$this->newBuilder( $vocabulary, $writer )->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertOrCreateNTriples( [ 'Q4_direct_foreignsource_properties' ], $writer );
	}

}
