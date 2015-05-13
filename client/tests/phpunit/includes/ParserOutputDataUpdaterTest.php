<?php

namespace Wikibase\Client\Tests;

use ParserOutput;
use Title;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\ParserOutputDataUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Client\ParserOutputDataUpdater
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ParserOutputDataUpdaterTest extends \MediaWikiTestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepo = null;

	private function getItems() {
		$items = array();

		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( 'en', 'Foo' );
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Foo de' );
		$links->addNewSiteLink( 'enwiki', 'Foo en', array( new ItemId( 'Q17' ) ) );
		$links->addNewSiteLink( 'srwiki', 'Foo sr' );
		$links->addNewSiteLink( 'dewiktionary', 'Foo de word' );
		$links->addNewSiteLink( 'enwiktionary', 'Foo en word' );
		$items[] = $item;

		$item = new Item( new ItemId( 'Q2' ) );
		$item->setLabel( 'en', 'Talk:Foo' );
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Talk:Foo de' );
		$links->addNewSiteLink( 'enwiki', 'Talk:Foo en' );
		$links->addNewSiteLink( 'srwiki', 'Talk:Foo sr' );
		$items[] = $item;

		return $items;
	}

	/**
	 * @param string[] $otherProjects
	 *
	 * @return ParserOutputDataUpdater
	 */
	private function getParserOutputDataUpdater( array $otherProjects = array() ) {
		$this->mockRepo = new MockRepository();

		foreach ( $this->getItems() as $item ) {
			$this->mockRepo->putEntity( $item );
		}

		return new ParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( $otherProjects ),
			$this->mockRepo,
			'srwiki'
		);
	}

	/**
	 * @param string[] $otherProjects
	 *
	 * @return OtherProjectsSidebarGeneratorFactory
	 */
	private function getOtherProjectsSidebarGeneratorFactory( array $otherProjects ) {
		$otherProjectsSidebarGenerator = $this->getOtherProjectsSidebarGenerator( $otherProjects );

		$otherProjectsSidebarGeneratorFactory = $this->getMockBuilder(
				'Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$otherProjectsSidebarGeneratorFactory->expects( $this->any() )
			->method( 'getOtherProjectsSidebarGenerator' )
			->will( $this->returnValue( $otherProjectsSidebarGenerator ) );

		return $otherProjectsSidebarGeneratorFactory;
	}

	/**
	 * @param string[] $otherProjects
	 *
	 * @return OtherProjectsSidebarGenerator
	 */
	private function getOtherProjectsSidebarGenerator( array $otherProjects ) {
		$otherProjectsSidebarGenerator = $this->getMockBuilder( 'Wikibase\Client\Hooks\OtherProjectsSidebarGenerator' )
			->disableOriginalConstructor()
			->getMock();

		$otherProjectsSidebarGenerator->expects( $this->any() )
			->method( 'buildProjectLinkSidebar' )
			->will( $this->returnValue( $otherProjects ) );

		return $otherProjectsSidebarGenerator;
	}

	public function testUpdateItemIdProperty() {
		$parserOutput = new ParserOutput();

		$titleText = 'Foo sr';
		$title = Title::newFromText( $titleText );

		$parserOutputDataUpdater = $this->getParserOutputDataUpdater();

		$parserOutputDataUpdater->updateItemIdProperty( $title, $parserOutput );
		$property = $parserOutput->getProperty( 'wikibase_item' );

		$itemId = $this->mockRepo->getItemIdForLink( 'srwiki', $titleText );
		$this->assertEquals( $itemId->getSerialization(), $property );

		$this->assertUsageTracking( $itemId, EntityUsage::SITELINK_USAGE, $parserOutput );
	}

	private function assertUsageTracking( ItemId $id, $aspect, ParserOutput $parserOutput ) {
		$usageAcc = new ParserOutputUsageAccumulator( $parserOutput );
		$usage = $usageAcc->getUsages();
		$expected = new EntityUsage( $id, $aspect );

		$this->assertContains( $expected, $usage, '', false, false );
	}

	public function testUpdateItemIdPropertyForUnconnectedPage() {
		$parserOutput = new ParserOutput();

		$titleText = 'Foo xx';
		$title = Title::newFromText( $titleText );

		$parserOutputDataUpdater = $this->getParserOutputDataUpdater();

		$parserOutputDataUpdater->updateItemIdProperty( $title, $parserOutput );
		$property = $parserOutput->getProperty( 'wikibase_item' );

		$this->assertEquals( false, $property );
	}

	/**
	 * @dataProvider updateOtherProjectsLinksDataProvider
	 */
	public function testUpdateOtherProjectsLinksData( $expected, $otherProjects, $titleText ) {
		$parserOutput = new ParserOutput();
		$title = Title::newFromText( $titleText );

		$parserOutputDataUpdater = $this->getParserOutputDataUpdater( $otherProjects );

		$parserOutputDataUpdater->updateOtherProjectsLinksData( $title, $parserOutput );
		$extensionData = $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' );

		$this->assertEquals( $expected, $extensionData );
	}

	public function updateOtherProjectsLinksDataProvider() {
		return array(
			array( array( 'project' => 'catswiki' ), array( 'project' => 'catswiki' ), 'Foo sr' ),
			array( array(), array(), 'Foo sr' ),
			array( array(), array(), 'Foo xx' )
		);
	}

}
