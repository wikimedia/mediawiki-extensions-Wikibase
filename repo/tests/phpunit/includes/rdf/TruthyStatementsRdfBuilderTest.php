<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\NullDedupeBag;
use Wikibase\Rdf\NullEntityMentionListener;
use Wikibase\Rdf\SnakRdfBuilder;
use Wikibase\Rdf\TruthyStatementRdfBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikibase\Rdf\TruthyStatementRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TruthyStatementRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData = null;

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData() {
		if ( $this->testData === null ) {
			$this->testData = new RdfBuilderTestData(
				__DIR__ . "/../../data/rdf",
				__DIR__ . "/../../data/rdf/TruthyStatementRdfBuilder"
			);
		}

		return $this->testData;
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

		$valueBuilder = $valueBuilderFactory->getSimpleValueSnakRdfBuilder(
			$vocabulary,
			$writer,
			new NullEntityMentionListener(),
			new NullDedupeBag()
		);

		$snakBuilder = new SnakRdfBuilder( $vocabulary, $valueBuilder, $this->getTestData()->getMockRepository() );

		$builder = new TruthyStatementRdfBuilder(
			$vocabulary,
			$writer,
			$snakBuilder
		);

		return $builder;
	}

	/**
	 * Extract text test data from RDF builder
	 * @param RdfWriter $writer
	 * @return string[] ntriples lines, sorted
	 */
	private function getDataFromWriter( RdfWriter $writer ) {
		$ntriples = $writer->drain();

		$lines = explode( "\n", trim( $ntriples ) );
		sort( $lines );
		$lines = array_map( 'trim', $lines );
		return $lines;
	}

	private function assertOrCreateNTriples( $dataSetName, RdfWriter $writer ) {
		$actualData = $this->getDataFromWriter( $writer );
		$correctData = $this->getTestData()->getNTriples( $dataSetName );

		if ( $correctData === null ) {
			$this->getTestData()->putTestData( $dataSetName, $actualData, '.actual' );
			$this->fail( 'Data set `' . $dataSetName . '` not found! Created file with the current data using the suffix .actual' );
		}

		$this->assertEquals( $correctData, $actualData, "Data set $dataSetName" );
	}

	public function provideAddEntity() {
		return array(
			array( 'Q4', 'Q4_statements' ),
		);
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$builder = $this->newBuilder( $writer );
		$builder->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddStatements( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$builder = $this->newBuilder( $writer );
		$builder->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
