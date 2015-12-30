<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\TermsRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

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
	 * @param RdfWriter $writer
	 * @param string[]|null $languages
	 *
	 * @return TermsRdfBuilder
	 */
	private function newBuilder( RdfWriter $writer, array $languages = null ) {
		$vocabulary = $this->getTestData()->getVocabulary();

		$builder = new TermsRdfBuilder(
			$vocabulary,
			$writer,
			$languages
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
			array( 'Q2', 'Q2_terms' ),
			array( 'Q2', 'Q2_terms_ru', array( 'ru' ) ),
		);
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $dataSetName, array $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$builder = $this->newBuilder( $writer, $languages );
		$builder->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
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
	public function testAddLabels( $entityName, $dataSetName, array $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$builder = $this->newBuilder( $writer, $languages );
		$builder->addLabels( $entity->getId(), $entity->getFingerprint()->getLabels() );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
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
	public function testAddDescriptions( $entityName, $dataSetName, array $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$builder = $this->newBuilder( $writer, $languages );
		$builder->addDescriptions( $entity->getId(), $entity->getFingerprint()->getDescriptions() );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
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
	public function testAddAliases( $entityName, $dataSetName, array $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$builder = $this->newBuilder( $writer, $languages );
		$builder->addAliases( $entity->getId(), $entity->getFingerprint()->getAliasGroups() );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
