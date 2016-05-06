<?php

namespace Wikibase\Repo\Tests\Hooks;

use CirrusSearch\Connection;
use CirrusSearch\Maintenance\MappingConfigBuilder;
use CirrusSearch;
use Elastica\Document;
use ParserOutput;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\ItemContent;
use Wikibase\Repo\Hooks\CirrusSearchHookHandlers;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\DescriptionsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\ItemFieldDefinitions;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\LabelsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\PropertyFieldDefinitions;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\TermSearchFieldDefinition;
use Wikibase\Repo\Search\Elastic\Indexer\EntityContentIndexer;
use Wikibase\Repo\Search\Elastic\Indexer\ItemIndexer;
use Wikibase\Repo\Search\Elastic\Indexer\PropertyIndexer;
use Wikibase\Repo\Search\Elastic\Mapping\MappingConfigModifier;

/**
 * @covers Wikibase\Repo\Hooks\CirrusSearchHookHandlers
 *
 * @since 0.5
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CirrusSearchHookHandlersTest extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		parent::setUp();

		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch is not available' );
		}
	}

	public function testOnCirrusSearchBuildDocumentParse() {
		$connection = $this->getMockBuilder( Connection::class )
			->disableOriginalConstructor()
			->getMock();

		$document = new Document();

		CirrusSearchHookHandlers::onCirrusSearchBuildDocumentParse(
			$document,
			Title::newFromText( 'Q1' ),
			$this->getContent(),
			new ParserOutput(),
			$connection
		);

		$this->assertSame( 'kitten', $document->get( 'label_en' ), 'label_en' );
		$this->assertSame( 'young cat', $document->get( 'description_en' ), 'description_en' );
		$this->assertSame( 1, $document->get( 'label_count' ), 'label_count' );
		$this->assertSame( 1, $document->get( 'sitelink_count' ), 'sitelink_count' );
		$this->assertSame( 1, $document->get( 'statement_count' ), 'statement_count' );
	}

	public function testOnCirrusSearchMappingConfig() {
		$mappingConfigBuilder = $this->getMockBuilder( MappingConfigBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$config = [
			'page' => [
				'properties' => []
			]
		];

		CirrusSearchHookHandlers::onCirrusSearchMappingConfig( $config, $mappingConfigBuilder );

		$this->assertArrayHasKey( 'label_en', $config['page']['properties'] );
		$this->assertArrayHasKey( 'description_de', $config['page']['properties'] );
		$this->assertArrayHasKey( 'label_count', $config['page']['properties'] );
		$this->assertArrayHasKey( 'sitelink_count', $config['page']['properties'] );
		$this->assertArrayHasKey( 'statement_count', $config['page']['properties'] );
	}

	public function testIndexExtraFields() {
		$document = new Document();

		$hookHandlers = $this->getCirrusSearchHookHandlers( [ 'en', 'es' ] );
		$hookHandlers->indexExtraFields( $this->getContent(), $document );

		$this->assertSame( 'kitten', $document->get( 'label_en' ), 'label_en' );
		$this->assertSame( 'young cat', $document->get( 'description_en' ), 'description_en' );
		$this->assertSame( 1, $document->get( 'label_count' ), 'label_count' );
		$this->assertSame( 1, $document->get( 'sitelink_count' ), 'sitelink_count' );
		$this->assertSame( 1, $document->get( 'statement_count' ), 'statement_count' );
	}

	public function testAddExtraFieldsToMappingConfig() {
		$hookHandlers = $this->getCirrusSearchHookHandlers( [ 'en' ] );

		$config = [
			'page' => [
				'properties' => []
			]
		];

		$hookHandlers->addExtraFieldsToMappingConfig( $config );

		$expected = [
			'page' => [
				'properties' => [
					'label_en' => [
						'type' => 'string'
					],
					'label_count' => [
						'type' => 'integer'
					],
					'description_en' => [
						'type' => 'string'
					],
					'sitelink_count' => [
						'type' => 'integer'
					],
					'statement_count' => [
						'type' => 'integer'
					]
				]
			]
		];

		$this->assertSame( $expected, $config );
	}

	private function getCirrusSearchHookHandlers( array $languageCodes ) {
		$termSearchFieldDefinition = new TermSearchFieldDefinition();
		$labelsProviderFieldDefinitions = new LabelsProviderFieldDefinitions(
			$termSearchFieldDefinition,
			$languageCodes
		);

		$descriptionsProviderFieldDefinitions = new DescriptionsProviderFieldDefinitions(
			$termSearchFieldDefinition,
			$languageCodes
		);

		$fieldDefinitions = [
			'item' => new ItemFieldDefinitions(
				$labelsProviderFieldDefinitions,
				$descriptionsProviderFieldDefinitions
			),
			'property' => new PropertyFieldDefinitions(
				$labelsProviderFieldDefinitions,
				$descriptionsProviderFieldDefinitions
			)
		];

		$mappingConfigModifier = new MappingConfigModifier( $fieldDefinitions );

		$entityContentIndexer = new EntityContentIndexer( [
			'item' => new ItemIndexer( $languageCodes ),
			'property' => new PropertyIndexer( $languageCodes )
		] );

		return new CirrusSearchHookHandlers(
			$mappingConfigModifier,
			$entityContentIndexer
		);
	}

	private function getContent() {
		$item = new Item();

		$item->getFingerprint()->setLabel( 'en', 'kitten' );
		$item->getFingerprint()->setDescription( 'en', 'young cat' );

		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );

		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P1' ) )
		);

		return ItemContent::newFromItem( $item );
	}

}
