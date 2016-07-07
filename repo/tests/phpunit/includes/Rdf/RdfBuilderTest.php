<?php

namespace Wikibase\Test\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\RdfBuilder;
use Wikibase\Rdf\RdfProducer;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers Wikibase\Rdf\RdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfBuilderTest extends \MediaWikiTestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	/**
	 * @var RdfBuilderTestData
	 */
	private $testData;

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
		if ( empty( $this->testData ) ) {
			$this->testData = new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/RdfBuilder'
			);
		}

		return $this->testData;
	}

	/**
	 * @param int $produce One of the RdfProducer::PRODUCE_... constants.
	 * @param DedupeBag|null $dedup
	 *
	 * @return RdfBuilder
	 */
	private function newRdfBuilder( $produce, DedupeBag $dedup = null ) {
		if ( $dedup === null ) {
			$dedup = new HashDedupeBag();
		}

		// Note: using the actual factory here makes this an integration test!
		// FIXME: we want to inject an ExternalIdentifierRdfBuilder here somehow!
		$valueBuilderFactory = WikibaseRepo::getDefaultInstance()->getValueSnakRdfBuilderFactory();

		$emitter = new NTriplesRdfWriter();
		$builder = new RdfBuilder(
			$this->getTestData()->getSiteList(),
			$this->getTestData()->getVocabulary(),
			$valueBuilderFactory,
			$this->getTestData()->getMockRepository(),
			$produce,
			$emitter,
			$dedup
		);

		$builder->startDocument();
		return $builder;
	}

	/**
	 * Load entity from JSON
	 *
	 * @param string $idString
	 *
	 * @return EntityDocument
	 */
	public function getEntityData( $idString ) {
		return $this->getTestData()->getEntity( $idString );
	}

	public function getRdfTests() {
		$rdfTests = array(
			array( 'Q1', 'Q1_simple' ),
			array( 'Q2', 'Q2_labels' ),
			array( 'Q3', 'Q3_links' ),
			array( 'Q4', 'Q4_claims' ),
			array( 'Q5', 'Q5_badges' ),
			array( 'Q6', 'Q6_qualifiers' ),
			array( 'Q7', 'Q7_references' ),
			array( 'Q8', 'Q8_baddates' ),
		);

		return $rdfTests;
	}

	/**
	 * @dataProvider getRdfTests
	 */
	public function testRdfBuild( $entityName, $dataSetName ) {
		$entity = $this->getEntityData( $entityName );
		$expected = $this->getTestData()->getNTriples( $dataSetName );

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL_STATEMENTS |
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
				RdfProducer::PRODUCE_QUALIFIERS |
				RdfProducer::PRODUCE_REFERENCES |
				RdfProducer::PRODUCE_SITELINKS |
				RdfProducer::PRODUCE_VERSION_INFO |
				RdfProducer::PRODUCE_FULL_VALUES );
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2014-11-04T03:11:05Z" );

		$this->helper->assertNTriplesEquals( $expected, $builder->getRDF() );
	}

	public function testAddEntityRedirect() {
		$builder = self::newRdfBuilder( 0 );

		$q1 = new ItemId( 'Q1' );
		$q11 = new ItemId( 'Q11' );
		$builder->addEntityRedirect( $q11, $q1 );

		$expected = '<http://acme.test/Q11> <http://www.w3.org/2002/07/owl#sameAs> <http://acme.test/Q1> .';
		$this->helper->assertNTriplesEquals( $expected, $builder->getRDF() );
	}

	public function getProduceOptions() {
		$produceTests = array(
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS, 'Q4_all_statements' ),
			array( 'Q4', RdfProducer::PRODUCE_TRUTHY_STATEMENTS, 'Q4_truthy_statements' ),
			array( 'Q6', RdfProducer::PRODUCE_ALL_STATEMENTS, 'Q6_no_qualifiers' ),
			array( 'Q6', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS, 'Q6_with_qualifiers' ),
			array( 'Q7', RdfProducer::PRODUCE_ALL_STATEMENTS , 'Q7_no_refs' ),
			array( 'Q7', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_REFERENCES, 'Q7_refs' ),
			array( 'Q3', RdfProducer::PRODUCE_SITELINKS, 'Q3_sitelinks' ),
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_PROPERTIES, 'Q4_props' ),
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_FULL_VALUES, 'Q4_values' ),
			array( 'Q1', RdfProducer::PRODUCE_VERSION_INFO, 'Q1_info' ),
			array( 'Q4', RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_RESOLVED_ENTITIES, 'Q4_resolved' ),
			array( 'Q10', RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_RESOLVED_ENTITIES, 'Q10_redirect' ),
		);

		return $produceTests;
	}

	/**
	 * @dataProvider getProduceOptions
	 */
	public function testRdfOptions( $entityName, $produceOption, $dataSetName ) {
		$entity = $this->getEntityData( $entityName );
		$expected = $this->getTestData()->getNTriples( $dataSetName );

		$builder = $this->newRdfBuilder( $produceOption );
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2013-10-04T03:31:05Z" );
		$builder->resolveMentionedEntities( $this->getTestData()->getMockRepository() );
		$this->helper->assertNTriplesEquals( $expected, $builder->getRDF() );
	}

	public function testDumpHeader() {
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_VERSION_INFO );
		$builder->addDumpHeader( 1426110695 );
		$expected = $this->getTestData()->getNTriples( 'dumpheader' );
		$this->helper->assertNTriplesEquals( $expected, $builder->getRDF() );
	}

	public function testDeduplication() {
		$bag = new HashDedupeBag();

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q7' ) );
		$data1 = $builder->getRDF();

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q9' ) );
		$data2 = $builder->getRDF();

		$expected = $this->getTestData()->getNTriples( 'Q7_Q9_dedup' );
		$this->helper->assertNTriplesEquals( $expected, $data1 . $data2 );
	}

}
