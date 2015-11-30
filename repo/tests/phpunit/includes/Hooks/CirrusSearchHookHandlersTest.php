<?php

namespace Wikibase\Repo\Tests\Hooks;

use Elastica\Document;
use ParserOutput;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Hooks\CirrusSearchHookHandlers;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Hooks\CirrusSearchHookHandlers
 *
 * @since 0.5
 *
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

		$document = new Document();
		$title = Title::newFromText( 'Q1' );

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P1' ) )
		);

		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$content = $entityContentFactory->newFromEntity( $item );

		$parserOutput = new ParserOutput();

		$connection = $this->getMockBuilder( 'CirrusSearch\Connection' )
			->disableOriginalConstructor()
			->getMock();

		CirrusSearchHookHandlers::onCirrusSearchBuildDocumentParse(
			$document,
			$title,
			$content,
			$parserOutput,
			$connection
		);

		$this->assertSame( 1, $document->get( 'sitelink_count' ), 'sitelink_count' );
		$this->assertSame( 1, $document->get( 'statement_count' ), 'statement_count' );
	}

	public function testOnCirrusSearchMappingConfig() {
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch is not available' );
		}
	}

}
