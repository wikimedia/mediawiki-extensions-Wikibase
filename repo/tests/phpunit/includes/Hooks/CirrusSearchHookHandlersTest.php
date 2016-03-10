<?php

namespace Wikibase\Repo\Tests\Hooks;

use CirrusSearch\Connection;
use CirrusSearch\Maintenance\MappingConfigBuilder;
use Elastica\Document;
use ParserOutput;
use PHPUnit_Framework_TestCase;
use Title;
use UnexpectedValueException;
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
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CirrusSearchHookHandlersTest extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		parent::setUp();

		if ( !class_exists( 'CirrusSearch' ) ) {
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

		$this->assertSame( 1, $document->get( 'label_count' ), 'label_count' );
		$this->assertSame( 1, $document->get( 'sitelink_count' ), 'sitelink_count' );
		$this->assertSame( 1, $document->get( 'statement_count' ), 'statement_count' );
	}

	public function testOnCirrusSearchMappingConfig() {
		$mappingConfigBuilder = $this->getMockBuilder( MappingConfigBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$config = array(
			'page' => array(
				'properties' => array()
			)
		);

		CirrusSearchHookHandlers::onCirrusSearchMappingConfig( $config, $mappingConfigBuilder );

		$this->assertSame(
			array( 'label_count', 'sitelink_count', 'statement_count' ),
			array_keys( $config['page']['properties'] )
		);
	}

	public function testIndexExtraFields() {
		$fieldDefinitions = $this->newFieldDefinitions();

		$document = new Document();
		$content = $this->getContent();

		$hookHandlers = new CirrusSearchHookHandlers( $fieldDefinitions );
		$hookHandlers->indexExtraFields( $document, $content );

		$this->assertSame( 1, $document->get( 'label_count' ), 'label_count' );
		$this->assertSame( 1, $document->get( 'sitelink_count' ), 'sitelink_count' );
		$this->assertSame( 1, $document->get( 'statement_count' ), 'statement_count' );
	}

	public function testAddExtraFieldsToMappingConfig() {
		$fieldDefinitions = $this->newFieldDefinitions();

		$config = array(
			'page' => array(
				'properties' => array()
			)
		);

		$hookHandlers = new CirrusSearchHookHandlers( $fieldDefinitions );
		$hookHandlers->addExtraFieldsToMappingConfig( $config );

		$expected = array(
			'page' => array(
				'properties' => array(
					'label_count' => array(
						'type' => 'integer'
					),
					'sitelink_count' => array(
						'type' => 'integer'
					),
					'statement_count' => array(
						'type' => 'integer'
					)
				)
			)
		);

		$this->assertSame( $expected, $config );
	}

	public function testAddExtraFields_throwsExceptionIfFieldNameAlreadySet() {
		$fieldDefinitions = $this->newFieldDefinitions();

		$config = array(
			'page' => array(
				'properties' => array(
					'sitelink_count' => array(
						'type' => 'long'
					)
				)
			)
		);

		$this->setExpectedException( UnexpectedValueException::class );

		$hookHandlers = new CirrusSearchHookHandlers( $fieldDefinitions );
		$hookHandlers->addExtraFieldsToMappingConfig( $config );
	}

	private function newFieldDefinitions() {
		// when we add multilingual fields, then WikibaseFieldDefinitions
		// will take WikibaseContentLanguages as an argument.
		return new WikibaseFieldDefinitions();
	}

	private function getContent() {
		$item = new Item();
		$item->getFingerprint()->setLabel( 'en', 'Kitten' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P1' ) )
		);

		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		return $entityContentFactory->newFromEntity( $item );
	}

}
