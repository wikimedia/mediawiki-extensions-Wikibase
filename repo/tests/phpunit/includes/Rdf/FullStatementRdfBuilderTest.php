<?php

namespace Wikibase\Repo\Tests\Rdf;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\FullStatementRdfBuilder;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\NullDedupeBag;
use Wikibase\Rdf\RdfProducer;
use Wikibase\Rdf\SnakRdfBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikibase\Rdf\FullStatementRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class FullStatementRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData = null;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData() {
		if ( $this->testData === null ) {
			$this->testData = new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/FullStatementRdfBuilder'
			);
		}

		return $this->testData;
	}

	/**
	 * @param RdfWriter $writer
	 * @param int $flavor Bitmap for the output flavor, use RdfProducer::PRODUCE_XXX constants.
	 * @param EntityId[] &$mentioned Receives any entity IDs being mentioned.
	 * @param DedupeBag|null $dedupe A bag of reference hashes that should be considered "already seen".
	 *
	 * @return FullStatementRdfBuilder
	 */
	private function newBuilder(
		RdfWriter $writer,
		$flavor,
		array &$mentioned = array(),
		DedupeBag $dedupe = null
	) {
		$vocabulary = $this->getTestData()->getVocabulary();

		$mentionTracker = $this->getMock( EntityMentionListener::class );
		$mentionTracker->expects( $this->any() )
			->method( 'propertyMentioned' )
			->will( $this->returnCallback( function( EntityId $id ) use ( &$mentioned ) {
				$key = $id->getSerialization();
				$mentioned[$key] = $id;
			} ) );

		// Note: using the actual factory here makes this an integration test!
		$valueBuilderFactory = WikibaseRepo::getDefaultInstance()->getValueSnakRdfBuilderFactory();

		if ( $flavor & RdfProducer::PRODUCE_FULL_VALUES ) {
			$valueWriter = $writer->sub();
		} else {
			$valueWriter = $writer;
		}

		$statementValueBuilder = $valueBuilderFactory->getValueSnakRdfBuilder(
			$flavor,
			$this->getTestData()->getVocabulary(),
			$valueWriter,
			$mentionTracker,
			new HashDedupeBag()
		);

		$snakRdfBuilder = new SnakRdfBuilder( $vocabulary, $statementValueBuilder, $this->getTestData()->getMockRepository() );
		$statementBuilder = new FullStatementRdfBuilder( $vocabulary, $writer, $snakRdfBuilder );
		$statementBuilder->setDedupeBag( $dedupe ?: new NullDedupeBag() );

		if ( $flavor & RdfProducer::PRODUCE_PROPERTIES ) {
			$snakRdfBuilder->setEntityMentionListener( $mentionTracker );
		}

		$statementBuilder->setProduceQualifiers( $flavor & RdfProducer::PRODUCE_QUALIFIERS );
		$statementBuilder->setProduceReferences( $flavor & RdfProducer::PRODUCE_REFERENCES );

		return $statementBuilder;
	}

	private function assertOrCreateNTriples( $dataSetName, RdfWriter $writer ) {
		$actual = $writer->drain();
		$expected = $this->getTestData()->getNTriples( $dataSetName );

		if ( $expected === null ) {
			$this->getTestData()->putTestData( $dataSetName, $actual, '.actual' );
			$this->fail( "Data set $dataSetName not found! Created file with the current data using"
				. " the suffix .actual" );
		}

		$this->helper->assertNTriplesEquals( $expected, $actual, "Data set $dataSetName" );
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

		$writer = $this->getTestData()->getNTriplesWriter();
		$mentioned = array();
		$this->newBuilder( $writer, $flavor, $mentioned )->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
		$this->assertEquals( $expectedMentions, array_keys( $mentioned ), 'Entities mentioned' );
	}

	public function provideAddEntity_seen() {
		return array(
			array( 'Q7', 'Q7_all_refs_seen', array( 'd2412760c57cacd8c8f24d9afde3b20c87161cca' ) ),
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

		$writer = $this->getTestData()->getNTriplesWriter();
		$mentioned = array();
		$this->newBuilder( $writer, RdfProducer::PRODUCE_ALL, $mentioned, $dedupe )
			->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
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

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer, RdfProducer::PRODUCE_ALL )
			->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
