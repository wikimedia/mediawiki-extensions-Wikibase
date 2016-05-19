<?php

namespace Wikibase\Client\Tests;

use ParserOutput;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ClientParserOutputDataUpdaterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepo = null;

	/**
	 * @return Item[]
	 */
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
		$links->addNewSiteLink( 'srwiki', 'Talk:Foo sr', array( new ItemId( 'Q17' ) ) );
		$items[] = $item;

		return $items;
	}

	/**
	 * @param string[] $otherProjects
	 *
	 * @return ClientParserOutputDataUpdater
	 */
	private function newInstance( array $otherProjects = array() ) {
		$this->mockRepo = new MockRepository();

		foreach ( $this->getItems() as $item ) {
			$this->mockRepo->putEntity( $item );
		}

		return new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( $otherProjects ),
			$this->mockRepo,
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
		$generator = $this->getOtherProjectsSidebarGenerator( $otherProjects );

		$factory = $this->getMockBuilder( OtherProjectsSidebarGeneratorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->any() )
			->method( 'getOtherProjectsSidebarGenerator' )
			->will( $this->returnValue( $generator ) );

		return $factory;
	}

	/**
	 * @param string $prefixedText
	 *
	 * @return Title
	 */
	private function getTitle( $prefixedText ) {
		$title = $this->getMock( Title::class );

		$title->expects( $this->once() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( $prefixedText ) );

		return $title;
	}

	/**
	 * @param string[] $otherProjects
	 *
	 * @return OtherProjectsSidebarGenerator
	 */
	private function getOtherProjectsSidebarGenerator( array $otherProjects ) {
		$generator = $this->getMockBuilder( OtherProjectsSidebarGenerator::class )
			->disableOriginalConstructor()
			->getMock();

		$generator->expects( $this->any() )
			->method( 'buildProjectLinkSidebar' )
			->will( $this->returnValue( $otherProjects ) );

		return $generator;
	}

	public function testUpdateItemIdProperty() {
		$parserOutput = new ParserOutput();

		$titleText = 'Foo sr';
		$title = $this->getTitle( $titleText );

		$instance = $this->newInstance();

		$instance->updateItemIdProperty( $title, $parserOutput );
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
		$title = $this->getTitle( $titleText );

		$instance = $this->newInstance();

		$instance->updateItemIdProperty( $title, $parserOutput );
		$property = $parserOutput->getProperty( 'wikibase_item' );

		$this->assertFalse( $property );
	}

	/**
	 * @dataProvider updateOtherProjectsLinksDataProvider
	 */
	public function testUpdateOtherProjectsLinksData( $expected, $otherProjects, $titleText ) {
		$parserOutput = new ParserOutput();
		$title = $this->getTitle( $titleText );

		$instance = $this->newInstance( $otherProjects );

		$instance->updateOtherProjectsLinksData( $title, $parserOutput );
		$extensionData = $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' );

		$this->assertEquals( $expected, $extensionData );
	}

	public function updateOtherProjectsLinksDataProvider() {
		return array(
			'other project exists, page has site link' => array(
				array( 'project' => 'catswiki' ),
				array( 'project' => 'catswiki' ),
				'Foo sr'
			),
			'other project exists, page has no site link' => array(
				array(),
				array( 'project' => 'catswiki' ),
				'Foo xx'
			),
			'no other projects, page has site link' => array(
				array(),
				array(),
				'Foo sr'
			),
			'no site link for this page' => array(
				array(),
				array(),
				'Foo xx'
			)
		);
	}

	public function testUpdateBadgesProperty() {
		$parserOutput = new ParserOutput();

		$title = $this->getTitle( 'Talk:Foo sr' );

		$instance = $this->newInstance();

		$instance->updateBadgesProperty( $title, $parserOutput );
		$this->assertTrue(
			$parserOutput->getProperty( 'wikibase-badge-Q17' ),
			'property "wikibase-badge-Q17" should be set'
		);
	}

	public function testUpdateBadgesProperty_removesPreviousData() {
		$parserOutput = new ParserOutput();
		$parserOutput->setProperty( 'wikibase-badge-Q17', true );

		$title = $this->getTitle( 'Foo sr' );

		$instance = $this->newInstance();

		$instance->updateBadgesProperty( $title, $parserOutput );
		$this->assertFalse(
			$parserOutput->getProperty( 'wikibase-badge-Q17' ),
			'property "wikibase-badge-Q17" should not be set'
		);
	}

	public function testUpdateBadgesProperty_inconsistentSiteLinkLookupEmptySiteLinkList() {
		$parserOutput = new ParserOutput();

		$title = $this->getTitle( 'Foo sr' );

		$siteLinkLookup = new MockRepository();
		$mockRepoNoSiteLinks = new MockRepository();
		foreach ( $this->getItems() as $item ) {
			$siteLinkLookup->putEntity( $item );

			$itemNoSiteLinks = $item->copy();
			$itemNoSiteLinks->setSiteLinkList( new SiteLinkList() );

			$mockRepoNoSiteLinks->putEntity( $itemNoSiteLinks );
		}

		$parserOutputDataUpdater = new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( array() ),
			$siteLinkLookup,
			$mockRepoNoSiteLinks,
			'srwiki'
		);

		// Suppress warnings as this is supposed to throw one.
		\MediaWiki\suppressWarnings();
		$parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutput );
		\MediaWiki\restoreWarnings();

		// Stuff didn't blow up
		$this->assertTrue( true );
	}

	public function testUpdateBadgesProperty_inconsistentSiteLinkLookupNoSuchEntity() {
		$parserOutput = new ParserOutput();

		$title = $this->getTitle( 'Foo sr' );

		$siteLinkLookup = new MockRepository();
		foreach ( $this->getItems() as $item ) {
			$siteLinkLookup->putEntity( $item );
		}

		$parserOutputDataUpdater = new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( array() ),
			$siteLinkLookup,
			new MockRepository(),
			'srwiki'
		);

		// Suppress warnings as this is supposed to throw one.
		\MediaWiki\suppressWarnings();
		$parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutput );
		\MediaWiki\restoreWarnings();

		// Stuff didn't blow up
		$this->assertTrue( true );
	}

}
