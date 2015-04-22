<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\SimpleValueRdfBuilder;
use Wikibase\Rdf\TruthyStatementRdfBuilder;

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
	 * @return TruthyStatementRdfBuilder
	 */
	private function newBuilder() {
		$vocabulary = $this->getTestData()->getVocabulary();
		$writer = $this->getTestData()->getNTriplesWriter();

		$valueBuilder = new SimpleValueRdfBuilder( $vocabulary, $this->getTestData()->getMockRepository() );

		$builder = new TruthyStatementRdfBuilder(
			$vocabulary,
			$writer,
			$valueBuilder
		);

		// HACK: stick the writer into a public field, for use by getDataFromBuilder()
		$builder->test_writer = $writer;

		return $builder;
	}

	/**
	 * Extract text test data from RDF builder
	 * @param TruthyStatementRdfBuilder $builder
	 * @return string[] ntriples lines, sorted
	 */
	private function getDataFromBuilder( TruthyStatementRdfBuilder $builder ) {
		// HACK: $builder->test_writer is glued on by newBuilder().
		$ntriples = $builder->test_writer->drain();

		$lines = explode( "\n", trim( $ntriples ) );
		sort( $lines );
		return $lines;
	}

	private function assertOrCreateNTriples( $dataSetName, TruthyStatementRdfBuilder $builder ) {
		$actualData = $this->getDataFromBuilder( $builder );
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

		$builder = $this->newBuilder();
		$builder->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddStatements( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$builder = $this->newBuilder();
		$builder->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

}
