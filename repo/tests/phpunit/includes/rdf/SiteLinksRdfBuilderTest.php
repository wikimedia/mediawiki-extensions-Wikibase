<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\RdfWriter;

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
				__DIR__ . "/../../data/rdf",
				__DIR__ . "/../../data/rdf/SiteLinksRdfBuilder"
			);
		}

		return $this->testData;
	}

	/**
	 * @param RdfWriter $writer
	 * @param string[]|null $sites
	 *
	 * @return SiteLinksRdfBuilder
	 */
	private function newBuilder( RdfWriter $writer, array $sites = null ) {
		$vocabulary = $this->getTestData()->getVocabulary();

		$builder = new SiteLinksRdfBuilder(
			$vocabulary,
			$writer,
			$this->getTestData()->getSiteList(),
			$sites
		);

		return $builder;
	}

	private function assertOrCreateNTriples( $dataSetName, RdfWriter $writer ) {
		$actualData = $writer->drain();
		$correctData = $this->getTestData()->getNTriples( $dataSetName );

		if ( $correctData === null ) {
			$this->getTestData()->putTestData( $dataSetName, $actualData, '.actual' );
			$this->fail( "Data set $dataSetName not found! Created file with the current data using"
				. " the suffix .actual" );
		}

		$this->helper->assertNTriplesEquals( $correctData, $actualData, "Data set $dataSetName" );
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
	public function testAddEntity( $entityName, $dataSetName, array $sites = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer, $sites )->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

	/**
	 * @dataProvider provideAddEntity
	 */
	public function testAddSiteLinks( $entityName, $dataSetName, array $sites = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer, $sites )->addSiteLinks( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
