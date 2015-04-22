<?php

namespace Wikibase\Test\Rdf;

use BagOStuff;
use HashBagOStuff;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Rdf\RdfBuilder;
use Wikibase\Rdf\RdfProducer;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers Wikibase\Rdf\RdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfBuilderTest extends MediaWikiTestCase {

	private $testData;

	/**
	 * Initialize repository data
	 *
	 * @return RdfBuilderTestData
	 */
	private function getTestData()
	{
		if( empty( $this->testData ) ) {
			$this->testData = new RdfBuilderTestData( __DIR__ . "/../../data/rdf", __DIR__ . "/../../data/rdf" );
		}

		return $this->testData;
	}

	/**
	 * @param int $produce
	 * @param BagOStuff|null $dedup
	 *
	 * @return RdfBuilder
	 */
	private function newRdfBuilder( $produce, BagOStuff $dedup = null ) {
		if( !$dedup ) {
			$dedup = new HashBagOStuff();
		}
		$emitter = new NTriplesRdfWriter();
		$builder = new RdfBuilder(
			$this->getTestData()->getSiteList(),
			$this->getTestData()->getVocabulary(),
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
	 * @return Entity
	 */
	public function getEntityData( $idString ) {
		return $this->getTestData()->getEntity( $idString );
	}

	/**
	 * Load serialized ntriples
	 *
	 * @param string $testName
	 *
	 * @return string[]|null
	 */
	public function getSerializedData( $testName ) {
		return $this->getTestData()->getNTriples( $testName );
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
	 * Extract text test data from RDF builder
	 * @param RdfBuilder $builder
	 * @return string[] ntriples lines
	 */
	private function getDataFromBuilder( RdfBuilder $builder ) {
		$data = $builder->getRDF();
		$dataSplit = explode( "\n", trim( $data ) );
		sort( $dataSplit );
		return $dataSplit;
	}

	/**
	 * @dataProvider getRdfTests
	 */
	public function testRdfBuild( $entityName, $dataSetName ) {
		$entity = $this->getEntityData( $entityName );
		$correctData = $this->getSerializedData( $dataSetName );

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL_STATEMENTS |
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
				RdfProducer::PRODUCE_QUALIFIERS |
				RdfProducer::PRODUCE_REFERENCES |
				RdfProducer::PRODUCE_SITELINKS |
				RdfProducer::PRODUCE_VERSION_INFO |
				RdfProducer::PRODUCE_FULL_VALUES);
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2014-11-04T03:11:05Z" );
		$this->assertEquals( $correctData, $this->getDataFromBuilder( $builder ) );
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
		);

		return $produceTests;
	}

	/**
	 * @dataProvider getProduceOptions
	 */
	public function testRdfOptions( $entityName, $produceOption, $dataSetName ) {
		$entity = $this->getEntityData( $entityName );
		$correctData = $this->getSerializedData( $dataSetName );

		$builder = $this->newRdfBuilder( $produceOption );
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2013-10-04T03:31:05Z" );
		$builder->resolveMentionedEntities( $this->getTestData()->getMockRepository() );
		$data = $this->getDataFromBuilder( $builder );
		$this->assertEquals( $correctData, $data);
	}

	public function testDumpHeader() {
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_VERSION_INFO );
		$builder->addDumpHeader( 1426110695 );
		$data = $this->getDataFromBuilder( $builder );
		$this->assertEquals( $this->getSerializedData( 'dumpheader' ),  $data);
	}

	public function testDeduplication() {
		$bag = new HashBagOStuff();
		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q7' ) );
		$data1 = $this->getDataFromBuilder( $builder );

		$builder = $this->newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q9' ) );
		$data2 = $this->getDataFromBuilder( $builder );

		$data = array_merge($data1, $data2);
		sort($data);

		$this->assertArrayEquals($this->getSerializedData( 'Q7_Q9_dedup' ), $data);
	}

}
