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

	public function provideAddEntityStub() {
		return [
			[ 'Q2', 'Q2_terms_stubs' ],
			[ 'Q2', 'Q2_terms_stubs_ru', [ 'ru' ] ],
		];
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
			->addLabels(
				RdfVocabulary::NS_ENTITY,
				$entity->getId()->getLocalPart(),
				$entity->getFingerprint()->getLabels()
			);

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
			->addDescriptions(
				RdfVocabulary::NS_ENTITY,
				$entity->getId()->getLocalPart(),
				$entity->getFingerprint()->getDescriptions()
			);

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
			->addAliases(
				RdfVocabulary::NS_ENTITY,
				$entity->getId()->getLocalPart(),
				$entity->getFingerprint()->getAliasGroups()
			);

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
