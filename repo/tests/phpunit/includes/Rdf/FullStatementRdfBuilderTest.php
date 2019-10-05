<?php

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
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
 * @covers \Wikibase\Rdf\FullStatementRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class FullStatementRdfBuilderTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/RdfBuilder'
			)
		);

		$this->helper->setAllBlanksEqual( true );
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
	 * @param int $flavor Bitmap for the output flavor, use RdfProducer::PRODUCE_XXX constants.
	 * @param EntityId[] &$mentioned Receives any entity IDs being mentioned.
	 * @param DedupeBag|null $dedupe A bag of reference hashes that should be considered "already seen".
	 *
	 * @return FullStatementRdfBuilder
	 */
	private function newBuilder(
		RdfWriter $writer,
		$flavor,
		array &$mentioned = [],
		DedupeBag $dedupe = null
	) {
		$vocabulary = $this->getTestData()->getVocabulary();

		$mentionTracker = $this->createMock( EntityMentionListener::class );
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

	private function newBuilderForEntitySourceBasedFederation(
		RdfWriter $writer,
		$flavor,
		array &$mentioned = [],
		DedupeBag $dedupe = null
	) {
		$vocabulary = $this->getTestData()->getVocabularyForEntitySourceBasedFederation();

		$mentionTracker = $this->createMock( EntityMentionListener::class );
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
			$vocabulary,
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

	/**
	 * @param string|string[] $dataSetNames
	 * @param RdfWriter $writer
	 */
	private function assertTriples( $dataSetNames, RdfWriter $writer ) {
		$actual = $writer->drain();
		$this->helper->assertNTriplesEqualsDataset( $dataSetNames, $actual );
	}

	public function provideAddEntity() {
		$props = array_map(
			function ( $data ) {
				/** @var PropertyId $propertyId */
				$propertyId = $data[0];
				return $propertyId->getSerialization();
			},
			$this->getTestData()->getTestProperties()
		);

		$q4_minimal = [ 'Q4_statements' ];
		$q4_all = [ 'Q4_statements', 'Q4_values' ];
		$q4_statements = [ 'Q4_statements' ];
		$q4_values = [ 'Q4_statements', 'Q4_values' ];
		$q6_no_qualifiers = [ 'Q6_statements' ];
		$q6_qualifiers = [ 'Q6_statements', 'Q6_qualifiers' ];
		$q7_no_refs = [ 'Q7_statements' ];
		$q7_refs = [ 'Q7_statements', 'Q7_reference_refs', 'Q7_references' ];

		return [
			[ 'Q4', 0, $q4_minimal, [] ],
			[ 'Q4', RdfProducer::PRODUCE_ALL, $q4_all, $props ],
			[ 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS, $q4_statements, [] ],
			[ 'Q6', RdfProducer::PRODUCE_ALL_STATEMENTS, $q6_no_qualifiers, [] ],
			[ 'Q6', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS, $q6_qualifiers, [] ],
			[ 'Q7', RdfProducer::PRODUCE_ALL_STATEMENTS , $q7_no_refs, [] ],
			[ 'Q7', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_REFERENCES, $q7_refs, [] ],
			[ 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_PROPERTIES, $q4_minimal, $props ],
			[ 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_FULL_VALUES, $q4_values, [] ],
		];
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $flavor, $dataSetNames, array $expectedMentions ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$mentioned = [];
		$this->newBuilder( $writer, $flavor, $mentioned )->addEntity( $entity );

		$this->assertTriples( $dataSetNames, $writer );
		$this->assertEquals( $expectedMentions, array_keys( $mentioned ), 'Entities mentioned' );
	}

	public function provideAddEntity_entityBasedFederation() {
		$props = array_map(
			function ( $data ) {
				/** @var PropertyId $propertyId */
				$propertyId = $data[0];
				return $propertyId->getSerialization();
			},
			$this->getTestData()->getTestProperties_noPrefixedIds()
		);

		$q4_minimal = [ 'Q4_statements_foreignsource_properties' ];
		$q4_all = [ 'Q4_statements_foreignsource_properties', 'Q4_values_foreignsource_properties' ];
		$q4_statements = [ 'Q4_statements_foreignsource_properties' ];
		$q4_values = [ 'Q4_statements_foreignsource_properties', 'Q4_values_foreignsource_properties' ];
		$q6_no_qualifiers = [ 'Q6_statements_foreignsource_properties' ];
		$q6_qualifiers = [ 'Q6_statements_foreignsource_properties', 'Q6_qualifiers_foreignsource_properties' ];
		$q7_no_refs = [ 'Q7_statements_foreignsource_properties' ];
		$q7_refs = [
			'Q7_statements_foreignsource_properties', 'Q7_reference_refs_foreignsource_properties', 'Q7_references_foreignsource_properties'
		];

		return [
			[ 'Q4_no_prefixed_ids', 0, $q4_minimal, [] ],
			[ 'Q4_no_prefixed_ids', RdfProducer::PRODUCE_ALL, $q4_all, $props ],
			[ 'Q4_no_prefixed_ids', RdfProducer::PRODUCE_ALL_STATEMENTS, $q4_statements, [] ],
			[ 'Q6_no_prefixed_ids', RdfProducer::PRODUCE_ALL_STATEMENTS, $q6_no_qualifiers, [] ],
			[ 'Q6_no_prefixed_ids', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS, $q6_qualifiers, [] ],
			[ 'Q7_no_prefixed_ids', RdfProducer::PRODUCE_ALL_STATEMENTS , $q7_no_refs, [] ],
			[ 'Q7_no_prefixed_ids', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_REFERENCES, $q7_refs, [] ],
			[ 'Q4_no_prefixed_ids', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_PROPERTIES, $q4_minimal, $props ],
			[ 'Q4_no_prefixed_ids', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_FULL_VALUES, $q4_values, [] ],
		];
	}

	/**
	 * @dataProvider provideAddEntity_entityBasedFederation
	 */
	public function testAddEntity_entityBasedFederation( $entityName, $flavor, $dataSetNames, array $expectedMentions ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$mentioned = [];
		$this->newBuilderForEntitySourceBasedFederation( $writer, $flavor, $mentioned )->addEntity( $entity );

		$this->assertTriples( $dataSetNames, $writer );
		$this->assertEquals( $expectedMentions, array_keys( $mentioned ), 'Entities mentioned' );
	}

	public function provideAddEntity_seen() {
		return [
			[ 'Q7', [ 'Q7_statements', 'Q7_reference_refs' ], [ 'f2693d55e4237eed12e76c87e54f6ae0f3d7080f' ] ],
		];
	}

	/**
	 * @dataProvider provideAddEntity_seen
	 */
	public function testAddEntity_seen( $entityName, $dataSetNames, array $referencesSeen ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$dedupe = new HashDedupeBag();

		foreach ( $referencesSeen as $hash ) {
			$dedupe->alreadySeen( $hash, 'R' );
		}

		$writer = $this->getTestData()->getNTriplesWriter();
		$mentioned = [];
		$this->newBuilder( $writer, RdfProducer::PRODUCE_ALL, $mentioned, $dedupe )
			->addEntity( $entity );

		$this->assertTriples( $dataSetNames, $writer );
	}

	/**
	 * @dataProvider provideAddEntity_seen
	 */
	public function testAddEntity_seen_entityBasedFederation() {
		$entity = $this->getTestData()->getEntity( 'Q7_no_prefixed_ids' );

		$dedupe = new HashDedupeBag();

		$dedupe->alreadySeen( 'd2412760c57cacd8c8f24d9afde3b20c87161cca', 'R' );

		$writer = $this->getTestData()->getNTriplesWriter();
		$mentioned = [];
		$this->newBuilderForEntitySourceBasedFederation( $writer, RdfProducer::PRODUCE_ALL, $mentioned, $dedupe )
			->addEntity( $entity );

		$this->assertTriples( [ 'Q7_statements_foreignsource_properties', 'Q7_reference_refs_foreignsource_properties' ], $writer );
	}

	public function provideAddStatements() {
		return [
			[ 'Q4', [ 'Q4_statements', 'Q4_values' ] ],
		];
	}

	/**
	 * @dataProvider provideAddStatements
	 */
	public function testAddStatements( $entityName, $dataSetNames ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer, RdfProducer::PRODUCE_ALL )
			->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertTriples( $dataSetNames, $writer );
	}

	public function testAddStatements_entityBasedFederation() {
		$entity = $this->getTestData()->getEntity( 'Q4_no_prefixed_ids' );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilderForEntitySourceBasedFederation( $writer, RdfProducer::PRODUCE_ALL )
			->addStatements( $entity->getId(), $entity->getStatements() );

		$this->assertTriples( [ 'Q4_statements_foreignsource_properties', 'Q4_values_foreignsource_properties' ], $writer );
	}

}
