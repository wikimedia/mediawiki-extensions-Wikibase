<?php

namespace Wikibase\Repo\Tests\Hooks;

use Elastica\Document;
use ParserOutput;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Search\Elastic\Fields\WikibaseFieldDefinitions;
use Wikibase\Repo\Hooks\CirrusSearchHookHandlers;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Hooks\CirrusSearchHookHandlers
 *
 * @since 0.5
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CirrusSearchHookHandlersTest extends PHPUnit_Framework_TestCase {

	public function testOnCirrusSearchBuildDocumentParse() {
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch is not available' );
		}

		$connection = $this->getMockBuilder( 'CirrusSearch\Connection' )
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

		$this->assertSame( 1, $document->get( 'sitelink_count' ), 'sitelink_count' );
		$this->assertSame( 1, $document->get( 'statement_count' ), 'statement_count' );
	}

	public function testOnCirrusSearchMappingConfig() {
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch is not available' );
		}

		$mappingConfigBuilder = $this->getMockBuilder(
				'CirrusSearch\Maintenance\MappingConfigBuilder'
			)
			->disableOriginalConstructor()
			->getMock();

		$config = array();

		CirrusSearchHookHandlers::onCirrusSearchMappingConfig( $config, $mappingConfigBuilder );

		$this->assertSame(
			array( 'sitelink_count', 'statement_count' ),
			array_keys( $config['page']['properties'] )
		);
	}

	public function testIndexExtraFields() {
		$fieldDefinitions = $this->newFieldDefinitions();

		$document = new Document();
		$content = $this->getContent();

		$hookHandlers = new CirrusSearchHookHandlers( $fieldDefinitions );
		$hookHandlers->indexExtraFields( $document, $content );

		$this->assertSame( 1, $document->get( 'sitelink_count' ), 'sitelink_count' );
		$this->assertSame( 1, $document->get( 'statement_count' ), 'statement_count' );
	}

	public function testAddExtraFields() {
		$fieldDefinitions = $this->newFieldDefinitions();

		$document = new Document();
		$content = $this->getContent();

		$config = array();

		$hookHandlers = new CirrusSearchHookHandlers( $fieldDefinitions );
		$hookHandlers->addExtraFields( $config );

		$expected = array(
			'page' => array(
				'properties' => array(
					'sitelink_count' => array(
						'type' => 'long'
					),
					'statement_count' => array(
						'type' => 'long'
					)
				)
			)
		);

		$this->assertSame( $expected, $config );
	}

	private function newFieldDefinitions() {
		// when we add multilingual fields, then WikibaseFieldDefinitions
		// will take WikibaseContentLanguages as an argument.
		return new WikibaseFieldDefinitions();
	}

	private function getContent() {
		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P1' ) )
		);

		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		return $entityContentFactory->newFromEntity( $item );
	}

}
