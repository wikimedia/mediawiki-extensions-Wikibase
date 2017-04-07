<?php

namespace Wikibase\Repo\Tests\Rdf;

use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\TermsRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikibase\Rdf\TermsRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TermsRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp() {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper(
			new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf/entities',
				__DIR__ . '/../../data/rdf/TermsRdfBuilder'
			)
		);
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

	private function assertOrCreateNTriples( $dataSetName, RdfWriter $writer ) {
		$actual = $writer->drain();
		$this->helper->assertNTriplesEqualsDataset( $dataSetName, $actual );
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
		$this->newBuilder( $writer, $languages )->addEntity( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

	public function provideAddEntityStub() {
		return array(
			array( 'Q2', 'Q2_terms_stubs' ),
			array( 'Q2', 'Q2_terms_stubs_ru', array( 'ru' ) ),
		);
	}

	/**
	 * @dataProvider provideAddEntityStub
	 */
	public function testAddEntityStub( $entityName, $dataSetName, array $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		$this->newBuilder( $writer, $languages )->addEntityStub( $entity );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
