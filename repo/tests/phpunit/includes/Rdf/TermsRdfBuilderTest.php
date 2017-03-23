<?php

namespace Wikibase\Repo\Tests\Rdf;

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

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData = null;

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
		$this->newBuilder( $writer, $languages )
			->addLabels( $entity->getId(), $entity->getFingerprint()->getLabels() );

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
		$this->newBuilder( $writer, $languages )
			->addDescriptions( $entity->getId(), $entity->getFingerprint()->getDescriptions() );

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
		$this->newBuilder( $writer, $languages )
			->addAliases( $entity->getId(), $entity->getFingerprint()->getAliasGroups() );

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
