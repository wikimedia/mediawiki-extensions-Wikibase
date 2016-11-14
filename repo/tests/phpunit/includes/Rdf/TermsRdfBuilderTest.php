<?php

namespace Wikibase\Test\Rdf;

use Wikibase\Rdf\TermsRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\RdfWriter;

/**
 * @covers Wikibase\Rdf\TermsRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
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
				__DIR__ . '/../../data/rdf/TermsRdfBuilder'
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
		return [
			[ 'Q2', 'Q2_terms' ],
			[ 'Q2', 'Q2_terms_ru', [ 'ru' ] ],
		];
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

	public function provideAddLabels() {
		return [
			[ 'Q2', 'Q2_terms_labels' ],
			[ 'Q2', 'Q2_terms_labels_ru', [ 'ru' ] ],
		];
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
		return [
			[ 'Q2', 'Q2_terms_descriptions' ],
			[ 'Q2', 'Q2_terms_descriptions_ru', [ 'ru' ] ],
		];
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
		return [
			[ 'Q2', 'Q2_terms_aliases' ],
			[ 'Q2', 'Q2_terms_aliases_ru', [ 'ru' ] ],
		];
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
