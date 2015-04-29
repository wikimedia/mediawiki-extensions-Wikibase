<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\ComplexValueRdfBuilder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\NullDedupeBag;
use Wikibase\Rdf\Test\RdfBuilderTestData;
use Wikibase\RdfProducer;
use Wikibase\Rdf\SimpleValueRdfBuilder;
use Wikibase\Rdf\FullStatementRdfBuilder;

/**
 * @covers Wikibase\FullStatementRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class FullStatementRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	private $testData;

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData()
	{
		if( empty( $this->testData ) ) {
			$this->testData = new RdfBuilderTestData(
				__DIR__ . "/../../data/rdf",
				__DIR__ . "/../../data/rdf/FullStatementRdfBuilder"
			);
		}
		return $this->testData;
	}

	/**
	 * @param int $flavor Bitmap for the output flavor, use RdfProducer::PRODUCE_XXX constants.
	 * @param EntityId[] &$mentioned Receives any entity IDs being mentioned.
	 * @param DedupeBag $dedupe A bag of reference hashes that should be considered "already seen".
	 *
	 * @return FullStatementRdfBuilder
	 */
	private function newBuilder( $flavor, array &$mentioned = array(), DedupeBag $dedupe = null ) {
		$vocabulary = $this->getTestData()->getVocabulary();

		$writer = $this->getTestData()->getNTriplesWriter();

		$mentionTracker = $this->getMock( 'Wikibase\Rdf\EntityMentionListener' );
		$mentionTracker->expects( $this->any() )
			->method( 'propertyMentioned' )
			->will( $this->returnCallback( function( EntityId $id ) use ( &$mentioned ) {
				$key = $id->getSerialization();
				$mentioned[$key] = $id;
			} ) );

		if ( $flavor & RdfProducer::PRODUCE_FULL_VALUES ) {
			$valueWriter = $writer->sub();

			$statementValueBuilder = new ComplexValueRdfBuilder( $vocabulary, $valueWriter, $this->getTestData()->getMockRepository() );
		} else {
			$statementValueBuilder = new SimpleValueRdfBuilder( $vocabulary, $this->getTestData()->getMockRepository() );
		}

		$statementBuilder = new FullStatementRdfBuilder( $vocabulary, $writer, $statementValueBuilder );
		$statementBuilder->setDedupeBag( $dedupe ?: new NullDedupeBag() );

		if ( $flavor & RdfProducer::PRODUCE_PROPERTIES  ) {
			$statementBuilder->setEntityMentionListener( $mentionTracker );
		}

		$statementBuilder->setProduceQualifiers( $flavor & RdfProducer::PRODUCE_QUALIFIERS );
		$statementBuilder->setProduceReferences( $flavor & RdfProducer::PRODUCE_REFERENCES );

		// HACK: stick the writer into a public field, for use by getDataFromBuilder()
		$statementBuilder->test_writer = $writer;

		return $statementBuilder;
	}

	/**
	 * Extract text test data from RDF builder
	 * @param FullStatementRdfBuilder $builder
	 * @return string[] ntriples lines, sorted
	 */
	private function getDataFromBuilder( FullStatementRdfBuilder $builder ) {
		// HACK: $builder->test_writer is glued on by newBuilder().
		$ntriples = $builder->test_writer->drain();

		$lines = explode( "\n", trim( $ntriples ) );
		sort( $lines );
		return $lines;
	}

	private function assertOrCreateNTriples( $dataSetName, FullStatementRdfBuilder $builder ) {
		$actualData = $this->getDataFromBuilder( $builder );
		$correctData = $this->getTestData()->getNTriples( $dataSetName );

		if ( $correctData === null ) {
			$this->getTestData()->putTestData( $dataSetName, $actualData, '.actual' );
			$this->fail( 'Data set `' . $dataSetName . '` not found! Created file with the current data using the suffix .actual' );
		}

		$this->assertEquals( $correctData, $actualData, "Data set $dataSetName" );
	}

	public function provideAddEntity() {
		$props = array( 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9' );

		return array(
			array( 'Q4', 0, 'Q4_minimal', array() ),
			array( 'Q4', RdfProducer::PRODUCE_ALL, 'Q4_all', $props ),
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS, 'Q4_statements', array() ),
			array( 'Q6', RdfProducer::PRODUCE_ALL_STATEMENTS, 'Q6_no_qualifiers', array() ),
			array( 'Q6', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS, 'Q6_with_qualifiers', array() ),
			array( 'Q7', RdfProducer::PRODUCE_ALL_STATEMENTS , 'Q7_no_refs', array() ),
			array( 'Q7', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_REFERENCES, 'Q7_refs', array() ),
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_PROPERTIES, 'Q4_minimal', $props ),
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_FULL_VALUES, 'Q4_values', array() ),
		);
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $flavor, $dataSetName, array $expectedMentions ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$mentioned = array();
		$builder = $this->newBuilder( $flavor, $mentioned );
		$builder->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
		$this->assertEquals( $expectedMentions, array_keys( $mentioned ), 'Entities mentioned' );
	}

	public function provideAddEntity_seen() {
		return array(
			array( 'Q7', 'Q7_all_refs_seen', array( '569a639de6dc67beb02644abfcf55534cb2f51ce' ) ),
		);
	}

	/**
	 * @dataProvider provideAddEntity_seen
	 */
	public function testAddEntity_seen( $entityName, $dataSetName, array $referencesSeen ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$dedupe = new HashDedupeBag();

		foreach ( $referencesSeen as $hash ) {
			$dedupe->alreadySeen( $hash, 'R' );
		}

		$mentioned = array();
		$builder = $this->newBuilder( RdfProducer::PRODUCE_ALL, $mentioned, $dedupe );
		$builder->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

	public function provideAddStatements() {
		return array(
			array( 'Q4', 'Q4_all' ),
		);
	}

	/**
	 * @dataProvider provideAddStatements
	 */
	public function testAddStatements( $entityName, $dataSetName ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$builder = $this->newBuilder( RdfProducer::PRODUCE_ALL );
		$builder->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

}
