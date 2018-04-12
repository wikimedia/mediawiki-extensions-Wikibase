<?php

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit4And6Compat;
use Wikibase\Rdf\NullDedupeBag;
use Wikibase\Rdf\NullEntityMentionListener;
use Wikibase\Rdf\RdfProducer;
use Wikibase\Rdf\SnakRdfBuilder;
use Wikibase\Rdf\TruthyStatementRdfBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikibase\Rdf\TruthyStatementRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 * @author Thiemo Kreuz
 */
class TruthyStatementRdfBuilderTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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
	 *
	 * @return TruthyStatementRdfBuilder
	 */
	private function newBuilder( RdfWriter $writer ) {
		$vocabulary = $this->getTestData()->getVocabulary();

		// Note: using the actual factory here makes this an integration test!
		$valueBuilderFactory = WikibaseRepo::getDefaultInstance()->getValueSnakRdfBuilderFactory();

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

	public function provideAddEntity() {
		return [
			[ 'Q4', [ 'Q4_direct' ] ],
		];
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer )->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddStatements( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer )->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
