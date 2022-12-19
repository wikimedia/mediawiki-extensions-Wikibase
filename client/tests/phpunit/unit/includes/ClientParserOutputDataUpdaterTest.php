<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit;

use Content;
use ParserOutput;
use Psr\Log\LogLevel;
use TestLogger;
use Title;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ClientParserOutputDataUpdaterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepo = null;

	/**
	 * @return Item[]
	 */
	private function getItems(): array {
		$items = [];

		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( 'en', 'Foo' );
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Foo de' );
		$links->addNewSiteLink( 'enwiki', 'Foo en', [ new ItemId( 'Q17' ) ] );
		$links->addNewSiteLink( 'srwiki', 'Foo sr' );
		$links->addNewSiteLink( 'dewiktionary', 'Foo de word' );
		$links->addNewSiteLink( 'enwiktionary', 'Foo en word' );
		$items[] = $item;

		$item = new Item( new ItemId( 'Q2' ) );
		$item->setLabel( 'en', 'Talk:Foo' );
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Talk:Foo de' );
		$links->addNewSiteLink( 'enwiki', 'Talk:Foo en' );
		$links->addNewSiteLink( 'srwiki', 'Talk:Foo sr', [ new ItemId( 'Q17' ) ] );
		$items[] = $item;

		return $items;
	}

	/**
	 * @param string[] $otherProjects
	 */
	private function newInstance(
		array $otherProjects = []
	): ClientParserOutputDataUpdater {
		$this->mockRepo = new MockRepository();

		foreach ( $this->getItems() as $item ) {
			$this->mockRepo->putEntity( $item );
		}

		return new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( $otherProjects ),
			$this->mockRepo,
			$this->mockRepo,
			$this->newUsageAccumulatorFactory(),
			'srwiki'
		);
	}

	private function newUsageAccumulatorFactory(): UsageAccumulatorFactory {
		return new UsageAccumulatorFactory(
			new EntityUsageFactory( new BasicEntityIdParser() ),
			new UsageDeduplicator( [] ),
			$this->createStub( EntityRedirectTargetLookup::class )
		);
	}

	/**
	 * @param string[] $otherProjects
	 */
	private function getOtherProjectsSidebarGeneratorFactory( array $otherProjects ): OtherProjectsSidebarGeneratorFactory {
		$generator = $this->getOtherProjectsSidebarGenerator( $otherProjects );

		$factory = $this->createMock( OtherProjectsSidebarGeneratorFactory::class );

		$factory->method( 'getOtherProjectsSidebarGenerator' )
			->willReturn( $generator );

		return $factory;
	}

	private function getTitle( string $prefixedText, bool $isRedirect = false ): Title {
		$title = $this->createMock( Title::class );

		$title->expects( $this->once() )
			->method( 'getPrefixedText' )
			->willReturn( $prefixedText );

		$title->method( 'isRedirect' )
			->willReturn( $isRedirect );

		$title->method( 'getNamespace' )
			->willReturn( NS_PROJECT );

		return $title;
	}

	/**
	 * @param string[] $otherProjects
	 */
	private function getOtherProjectsSidebarGenerator( array $otherProjects ): OtherProjectsSidebarGenerator {
		$generator = $this->createMock( OtherProjectsSidebarGenerator::class );

		$generator->method( 'buildProjectLinkSidebar' )
			->willReturn( $otherProjects );

		return $generator;
	}

	public function testUpdateItemIdProperty(): void {
		$parserOutput = new ParserOutput();

		$titleText = 'Foo sr';
		$title = $this->getTitle( $titleText );

		$instance = $this->newInstance();

		$instance->updateItemIdProperty( $title, $parserOutput );
		$property = $parserOutput->getPageProperty( 'wikibase_item' );

		$itemId = $this->mockRepo->getItemIdForLink( 'srwiki', $titleText );
		$this->assertEquals( $itemId->getSerialization(), $property );

		$this->assertUsageTracking( $itemId, EntityUsage::SITELINK_USAGE, $parserOutput );
	}

	private function assertUsageTracking( ItemId $id, $aspect, ParserOutput $parserOutput ): void {
		$usageAcc = $this->newUsageAccumulatorFactory()->newFromParserOutput( $parserOutput );
		$usage = $usageAcc->getUsages();
		$expected = new EntityUsage( $id, $aspect );

		$this->assertContainsEquals( $expected, $usage );
	}

	public function testUpdateItemIdPropertyForUnconnectedPage(): void {
		$parserOutput = new ParserOutput();

		$titleText = 'Foo xx';
		$title = $this->getTitle( $titleText );

		$instance = $this->newInstance();

		$instance->updateItemIdProperty( $title, $parserOutput );
		$property = $parserOutput->getPageProperty( 'wikibase_item' );

		$this->assertNull( $property );
	}

	/**
	 * @dataProvider updateOtherProjectsLinksDataProvider
	 */
	public function testUpdateOtherProjectsLinksData( array $expected, array $otherProjects, string $titleText ): void {
		$parserOutput = new ParserOutput();
		$title = $this->getTitle( $titleText );

		$instance = $this->newInstance( $otherProjects );

		$instance->updateOtherProjectsLinksData( $title, $parserOutput );
		$extensionData = $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' );

		$this->assertEquals( $expected, $extensionData );
	}

	public function updateOtherProjectsLinksDataProvider(): array {
		return [
			'other project exists, page has site link' => [
				[ 'project' => 'catswiki' ],
				[ 'project' => 'catswiki' ],
				'Foo sr',
			],
			'other project exists, page has no site link' => [
				[],
				[ 'project' => 'catswiki' ],
				'Foo xx',
			],
			'no other projects, page has site link' => [
				[],
				[],
				'Foo sr',
			],
			'no site link for this page' => [
				[],
				[],
				'Foo xx',
			],
		];
	}

	public function testUpdateBadgesProperty(): void {
		$parserOutput = new ParserOutput();

		$title = $this->getTitle( 'Talk:Foo sr' );

		$instance = $this->newInstance();

		$instance->updateBadgesProperty( $title, $parserOutput );
		$this->assertTrue(
			$parserOutput->getPageProperty( 'wikibase-badge-Q17' ),
			'property "wikibase-badge-Q17" should be set'
		);
	}

	public function testUpdateBadgesProperty_removesPreviousData(): void {
		$parserOutput = new ParserOutput();
		$parserOutput->setPageProperty( 'wikibase-badge-Q17', true );

		$title = $this->getTitle( 'Foo sr' );

		$instance = $this->newInstance();

		$instance->updateBadgesProperty( $title, $parserOutput );
		$this->assertNull(
			$parserOutput->getPageProperty( 'wikibase-badge-Q17' ),
			'property "wikibase-badge-Q17" should not be set'
		);
	}

	public function testUpdateBadgesProperty_inconsistentSiteLinkLookupEmptySiteLinkList(): void {
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

		$logger = new TestLogger( true );

		$parserOutputDataUpdater = new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( [] ),
			$siteLinkLookup,
			$mockRepoNoSiteLinks,
			$this->newUsageAccumulatorFactory(),
			'srwiki',
			$logger
		);

		$parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutput );
		$logs = $logger->getBuffer();

		$this->assertCount( 1, $logs );
		$this->assertSame( LogLevel::WARNING, $logs[0][0] );
	}

	public function testUpdateBadgesProperty_inconsistentSiteLinkLookupNoSuchEntity(): void {
		$parserOutput = new ParserOutput();

		$title = $this->getTitle( 'Foo sr' );

		$siteLinkLookup = new MockRepository();
		foreach ( $this->getItems() as $item ) {
			$siteLinkLookup->putEntity( $item );
		}

		$logger = new TestLogger( true );

		$parserOutputDataUpdater = new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( [] ),
			$siteLinkLookup,
			new MockRepository(),
			$this->newUsageAccumulatorFactory(),
			'srwiki',
			$logger
		);

		$parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutput );
		$logs = $logger->getBuffer();

		$this->assertCount( 1, $logs );
		$this->assertSame( LogLevel::WARNING, $logs[0][0] );
	}

	public function updateTrackingCategoriesDataProvider(): array {
		return [
			[ 'Foo sr', false, 0 ],
			[ 'Foo sr', true, 1 ],
			[ 'Foo xx', false, 0 ],
			[ 'Foo xx', true, 0 ],
		];
	}

	/**
	 * @dataProvider updateTrackingCategoriesDataProvider
	 */
	public function testUpdateTrackingCategories( string $titleText, bool $isRedirect, int $expected ): void {
		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->expects( $this->exactly( $expected ) )
			->method( 'addCategory' );

		$title = $this->getTitle( $titleText, $isRedirect );

		$instance = $this->newInstance();
		$instance->updateTrackingCategories( $title, $parserOutput );
	}

	public function updateUnconnectedPagePropertyProvider() {
		return [
			'Linked page, nothing to do' => [
				'expectedPageProps' => [],
				'priorPageProps' => [],
				'titleText' => 'Foo sr',
			],
			'Unlinked page with expectedUnconnectedPage' => [
				'expectedPageProps' => [ 'expectedUnconnectedPage' => '' ],
				'priorPageProps' => [ 'expectedUnconnectedPage' => '' ],
				'titleText' => 'Foo xx',
			],
			'Unlinked page without expectedUnconnectedPage' => [
				'expectedPageProps' => [ 'unexpectedUnconnectedPage' => -NS_PROJECT ],
				'priorPageProps' => [],
				'titleText' => 'Foo xx',
			],
			'Redirect page' => [
				'expectedPageProps' => [],
				'priorPageProps' => [],
				'titleText' => 'Foo xx',
				'isRedirect' => true,
			],
		];
	}

	/**
	 * @dataProvider updateUnconnectedPagePropertyProvider
	 */
	public function testUpdateUnconnectedPageProperty(
		array $expectedPageProps,
		array $priorPageProps,
		string $titleText,
		bool $isRedirect = false
	): void {
		$parserOutput = new ParserOutput();
		foreach ( $priorPageProps as $key => $value ) {
			$parserOutput->setPageProperty( $key, $value );
		}

		$content = $this->createMock( Content::class );
		$content->method( 'isRedirect' )
			->willReturn( $isRedirect );

		// don’t pass $isRedirect into getTitle(), shouldn’t be used because it might be outdated
		$title = $this->getTitle( $titleText );

		$instance = $this->newInstance( [] );

		$instance->updateUnconnectedPageProperty( $content, $title, $parserOutput );

		$this->assertSame( $expectedPageProps, $parserOutput->getPageProperties() );
	}

}
