<?php

namespace Wikibase\Repo\Tests\Rdf;

use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\TermsRdfBuilder;
use Wikimedia\Purtle\RdfWriter;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Rdf\TermsRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TermsRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
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
			[],
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

	public function provideAddLabels() {
		return [
			[ 'Q2', 'Q2_terms_labels' ],
			[ 'Q2', 'Q2_terms_labels_ru', [ 'ru' ] ],
		];
	}

	private static $DEFAULT_LABELS = [
		[ 'rdfs', 'label' ],
		[ RdfVocabulary::NS_SKOS, 'prefLabel' ],
		[ RdfVocabulary::NS_SCHEMA_ORG, 'name' ],
	];

	/**
	 * @dataProvider provideAddLabels
	 */
	public function testAddLabels( $entityName, $dataSetName, array $languages = null ) {
		$entity = $this->getTestData()->getEntity( $entityName );

		$writer = $this->getTestData()->getNTriplesWriter();
		TestingAccessWrapper::newFromObject( $this->newBuilder( $writer, $languages ) )
			->addLabels(
				RdfVocabulary::NS_ENTITY,
				$entity->getId()->getLocalPart(),
				$entity->getFingerprint()->getLabels(),
				self::$DEFAULT_LABELS
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
		TestingAccessWrapper::newFromObject( $this->newBuilder( $writer, $languages ) )
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
		TestingAccessWrapper::newFromObject( $this->newBuilder( $writer, $languages ) )
			->addAliases(
				RdfVocabulary::NS_ENTITY,
				$entity->getId()->getLocalPart(),
				$entity->getFingerprint()->getAliasGroups()
			);

		$this->assertOrCreateNTriples( $dataSetName, $writer );
	}

}
