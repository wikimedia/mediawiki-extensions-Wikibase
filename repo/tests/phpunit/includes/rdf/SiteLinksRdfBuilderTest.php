<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\SiteLinksRdfBuilder;

/**
 * @covers Wikibase\Rdf\SiteLinksRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class SiteLinksRdfBuilderTest extends \PHPUnit_Framework_TestCase {

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
				__DIR__ . "/../../data/rdf/SiteLinksRdfBuilder"
			);
		}

		return $this->testData;
	}

	/**
	 * @param string[]|null $sites
	 *
	 * @return SiteLinksRdfBuilder
	 */
	private function newBuilder( $sites = null ) {
		$vocabulary = $this->getTestData()->getVocabulary();

		$writer = $this->getTestData()->getNTriplesWriter();

		$builder = new SiteLinksRdfBuilder(
			$vocabulary,
			$writer,
			$this->getTestData()->getSiteList(),
			$sites
		);

		// HACK: stick the writer into a public field, for use by getDataFromBuilder()
		$builder->test_writer = $writer;

		return $builder;
	}

	/**
	 * Extract text test data from RDF builder
	 * @param SiteLinksRdfBuilder $builder
	 * @return string[] ntriples lines, sorted
	 */
	private function getDataFromBuilder( SiteLinksRdfBuilder $builder ) {
		// HACK: $builder->test_writer is glued on by newBuilder().
		$ntriples = $builder->test_writer->drain();

		$lines = explode( "\n", trim( $ntriples ) );
		sort( $lines );
		return $lines;
	}

	private function assertOrCreateNTriples( $dataSetName, SiteLinksRdfBuilder $builder ) {
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
			array( 'Q3', 'Q3_sitelinks' ),
			array( 'Q3', 'Q3_sitelinks_ruwiki', array( 'ruwiki' ) ),
		);
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( $entityName, $dataSetName, $sites = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$builder = $this->newBuilder( $sites );
		$builder->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddSiteLinks( $entityName, $dataSetName, $sites = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$builder = $this->newBuilder( $sites );
		$builder->addSiteLinks( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $builder );
	}

}
