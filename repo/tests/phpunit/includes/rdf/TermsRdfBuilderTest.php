<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\TermsRdfBuilder;

/**
 * @covers Wikibase\Rdf\TermsRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TermsRdfBuilderTest extends \PHPUnit_Framework_TestCase {

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
				__DIR__ . "/../../data/rdf/TermsRdfBuilder"
			);
		}

		return $this->testData;
	}

	/**
	 * @param string[]|null $languages
	 *
	 * @return TermsRdfBuilder
	 */
	private function newBuilder( $languages = null ) {
		$vocabulary = $this->getTestData()->getVocabulary();

		$writer = $this->getTestData()->getNTriplesWriter();

		$builder = new TermsRdfBuilder(
			$vocabulary,
			$writer,
			$languages
		);

		// HACK: stick the writer into a public field, for use by getDataFromBuilder()
		$builder->test_writer = $writer;

		return $builder;
	}

	/**
	 * Extract text test data from RDF builder
	 * @param TermsRdfBuilder $builder
	 * @return string[] ntriples lines, sorted
	 */
	private function getDataFromBuilder( TermsRdfBuilder $builder ) {
		// HACK: $builder->test_writer is glued on by newBuilder().
		$ntriples = $builder->test_writer->drain();

		$lines = explode( "\n", trim( $ntriples ) );
		sort( $lines );
		return $lines;
	}

	private function assertOrCreateNTriples( $dataSetName, TermsRdfBuilder $builder ) {
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
			array( 'Q2', 'Q2_terms' ),
			array( 'Q2', 'Q2_terms_ru', array( 'ru' ) ),
		);
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $dataSetName, $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$builder = $this->newBuilder( $languages );
		$builder->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

	public function provideAddLabels() {
		return array(
			array( 'Q2', 'Q2_terms_labels' ),
			array( 'Q2', 'Q2_terms_labels_ru', array( 'ru' ) ),
		);
	}

	/**
	 * @dataProvider provideAddLabels
	 */
	public function testAddLabels( $entityName, $dataSetName, $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$builder = $this->newBuilder( $languages );
		$builder->addLabels( $entity->getId(), $entity->getFingerprint()->getLabels() );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

	public function provideAddDescriptions() {
		return array(
			array( 'Q2', 'Q2_terms_descriptions' ),
			array( 'Q2', 'Q2_terms_descriptions_ru', array( 'ru' ) ),
		);
	}

	/**
	 * @dataProvider provideAddDescriptions
	 */
	public function testAddDescriptions( $entityName, $dataSetName, $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$builder = $this->newBuilder( $languages );
		$builder->addDescriptions( $entity->getId(), $entity->getFingerprint()->getDescriptions() );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

	public function provideAddAliases() {
		return array(
			array( 'Q2', 'Q2_terms_aliases' ),
			array( 'Q2', 'Q2_terms_aliases_ru', array( 'ru' ) ),
		);
	}

	/**
	 * @dataProvider provideAddAliases
	 */
	public function testAddAliases( $entityName, $dataSetName, $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$builder = $this->newBuilder( $languages );
		$builder->addAliases( $entity->getId(), $entity->getFingerprint()->getAliasGroups() );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

}
