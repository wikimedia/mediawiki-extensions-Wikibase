<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use Content;
use HashSiteStore;
use MediaWiki\HookContainer\HookContainer;
use MediaWikiIntegrationTestCase;
use MediaWikiSite;
use ParserOutput;
use Psr\Log\NullLogger;
use Site;
use SiteLookup;
use Title;
use Wikibase\Client\Hooks\LangLinkHandlerFactory;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\NoLangLinkHandler;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\ParserOutputUpdateHookHandler;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Client\Hooks\ParserOutputUpdateHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ParserOutputUpdateHookHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param string $globalId
	 * @param string $group
	 * @param string $languageCode
	 *
	 * @return Site
	 */
	private function newSite( $globalId, $group, $languageCode ) {
		$site = new MediaWikiSite();
		$site->setGlobalId( $globalId );
		$site->setGroup( $group );
		$site->setLanguageCode( $languageCode );
		$site->addNavigationId( $languageCode );
		$site->setPagePath( 'wiki/' );
		$site->setFilePath( 'w/' );
		$site->setLinkPath( 'http://' . $globalId . '.test.com/wiki/$1' );

		return $site;
	}

	/**
	 * @return SiteLookup
	 */
	private function getSiteLookup() {
		$siteLookup = new HashSiteStore( [
			$this->newSite( 'wikidatawiki', 'wikidata', 'en' ),
			$this->newSite( 'commonswiki', 'commons', 'en' ),
			$this->newSite( 'enwiki', 'wikipedia', 'en' ),
			$this->newSite( 'dewiki', 'wikipedia', 'de' ),
		] );

		return $siteLookup;
	}

	private function getBadgeItem() {
		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'de', 'exzellent' );
		$item->setLabel( 'en', 'featured' );

		return $item;
	}

	private function getTestSiteLinkData() {
		$badgeId = $this->getBadgeItem()->getId();

		return [
			'Q1' => [
				new SiteLink( 'dewiki', 'Sauerstoff', [ $badgeId ] ),
				new SiteLink( 'enwiki', 'Oxygen' ),
				new SiteLink( 'commonswiki', 'Oxygen' ),
			],
		];
	}

	private function getTestSiteLinkDataInNotEnabledNamespace() {
		return [
			'Q7' => [
				new SiteLink( 'dewiki', 'User:Foo' ),
				new SiteLink( 'enwiki', 'User:Foo' ),
				new SiteLink( 'commonswiki', 'User:Foo' ),
			],
		];
	}

	/**
	 * @param ItemId $id
	 * @param SiteLink[] $links
	 *
	 * @return Item
	 */
	private function newItem( ItemId $id, array $links ) {
		$item = new Item( $id );
		$item->setSiteLinkList( new SiteLinkList( $links ) );
		return $item;
	}

	/**
	 * @param array[] $links
	 *
	 * @return MockRepository
	 */
	private function getMockRepo( array $links ) {
		$repo = new MockRepository();

		foreach ( $links as $itemKey => $itemLinks ) {
			$itemId = new ItemId( $itemKey );
			$item = $this->newItem( $itemId, $itemLinks );
			$repo->putEntity( $item );
		}

		return $repo;
	}

	/**
	 * @return SettingsArray
	 */
	private function newSettings() {
		$defaults = [
			'siteGlobalID' => 'enwiki',
			'languageLinkAllowedSiteGroups' => [ 'wikipedia' ],
			'namespaces' => [ NS_MAIN, NS_CATEGORY ],
			'otherProjectsLinks' => [ 'commonswiki' ],
		];

		return new SettingsArray( $defaults );
	}

	private function newUsageAccumulatorFactory(): UsageAccumulatorFactory {
		return new UsageAccumulatorFactory(
			new EntityUsageFactory( new BasicEntityIdParser() ),
			new UsageDeduplicator( [] ),
			$this->createStub( EntityRedirectTargetLookup::class )
		);
	}

	private function newParserOutputUpdateHookHandler( array $siteLinkData ) {
		$settings = $this->newSettings();

		$namespaceChecker = $this->newNamespaceChecker();

		$mockRepo = $this->getMockRepo( $siteLinkData );
		$mockRepo->putEntity( $this->getBadgeItem() );

		$parserOutputDataUpdater = $this->newParserOutputDataUpdater( $mockRepo, $siteLinkData, $settings );

		$langLinkHandlerFactory = $this->newLangLinkHandlerFactory( $namespaceChecker, $mockRepo, $settings );

		return new ParserOutputUpdateHookHandler(
			$langLinkHandlerFactory,
			$namespaceChecker,
			$parserOutputDataUpdater,
			$this->newUsageAccumulatorFactory()
		);
	}

	private function getBadgeDisplay() {
		$labelDescriptionLookup = $this->createMock( LabelDescriptionLookup::class );

		$labelDescriptionLookup->method( 'getLabel' )
			->willReturn( new Term( 'en', 'featured' ) );

		return new LanguageLinkBadgeDisplay(
			new SidebarLinkBadgeDisplay(
				$labelDescriptionLookup,
				[ 'Q17' => 'featured' ],
				$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
			)
		);
	}

	private function getEntityLookup( array $siteLinkData ) {
		$lookup = new InMemoryEntityLookup();

		foreach ( $siteLinkData as $itemId => $siteLinks ) {
			$item = new Item( new ItemId( $itemId ) );
			$item->setSiteLinkList( new SiteLinkList( $siteLinks ) );
			$lookup->addEntity( $item );
		}

		return $lookup;
	}

	private function getOtherProjectsSidebarGeneratorFactory(
		SettingsArray $settings,
		SiteLinkLookup $siteLinkLookup,
		array $siteLinkData
	) {
		$sidebarLinkBadgeDisplay = new SidebarLinkBadgeDisplay(
			$this->createMock( LabelDescriptionLookup::class ),
			[],
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
		);

		return new OtherProjectsSidebarGeneratorFactory(
			$settings,
			$siteLinkLookup,
			$this->getSiteLookup(),
			$this->getEntityLookup( $siteLinkData ),
			$sidebarLinkBadgeDisplay,
			$this->createMock( HookContainer::class ),
			new NullLogger()
		);
	}

	private function newParserOutput( array $extensionDataAppend, array $extensionDataSet ) {
		$parserOutput = new ParserOutput();

		foreach ( $extensionDataAppend as $name => $value ) {
			foreach ( $value as $item ) {
				$parserOutput->appendExtensionData( $name, $item );
			}
		}

		foreach ( $extensionDataSet as $key => $value ) {
			$parserOutput->setExtensionData( $key, $value );
		}

		return $parserOutput;
	}

	public function parserAfterParseProvider() {
		$commonsOxygen = [
			'msg' => 'wikibase-otherprojects-commons',
			'class' => 'wb-otherproject-link wb-otherproject-commons',
			'href' => 'http://commonswiki.test.com/wiki/Oxygen',
			'hreflang' => 'en',
		];

		$badgesQ1 = [
			'class' => 'badge-Q17 featured',
			'label' => 'featured',
		];

		return [
			'repo-links' => [
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				[],
				[ 'de:Sauerstoff' ],
				[ $commonsOxygen ],
				[ 'de' => $badgesQ1 ],
			],

			'noexternallanglinks=*' => [
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				[ NoLangLinkHandler::EXTENSION_DATA_KEY => [ '*' ] ],
				[],
				[ $commonsOxygen ],
				null,
			],

			'noexternallanglinks=de' => [
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				[ NoLangLinkHandler::EXTENSION_DATA_KEY => [ 'de' ] ],
				[],
				[ $commonsOxygen ],
				[],
			],

			'noexternallanglinks=ja' => [
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				[ NoLangLinkHandler::EXTENSION_DATA_KEY => [ 'ja' ] ],
				[ 'de:Sauerstoff' ],
				[ $commonsOxygen ],
				[ 'de' => $badgesQ1 ],
			],
		];
	}

	/**
	 * @dataProvider parserAfterParseProvider
	 */
	public function testDoContentAlterParserOutput(
		Title $title,
		$expectedItem,
		array $extensionDataAppend,
		array $expectedLanguageLinks,
		array $expectedSisterLinks,
		array $expectedBadges = null
	) {
		$content = $this->createMock( Content::class );
		$parserOutput = $this->newParserOutput( $extensionDataAppend, [] );
		$handler = $this->newParserOutputUpdateHookHandler( $this->getTestSiteLinkData() );

		$handler->doContentAlterParserOutput( $content, $title, $parserOutput );

		$expectedUsage = new EntityUsage(
			new ItemId( $expectedItem ),
			EntityUsage::SITELINK_USAGE
		);

		$this->assertEquals( $expectedItem, $parserOutput->getPageProperty( 'wikibase_item' ) );
		$this->assertNull( $parserOutput->getPageProperty( 'unexpectedUnconnectedPage' ) );

		$this->assertLanguageLinks( $expectedLanguageLinks, $parserOutput );
		$this->assertSisterLinks( $expectedSisterLinks, $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$actualUsage = $parserOutput->getExtensionData(
			ParserOutputUsageAccumulator::EXTENSION_DATA_KEY
		);

		$this->assertEquals( [ $expectedUsage->getIdentityString() ], array_keys( $actualUsage ) );

		$actualBadges = $parserOutput->getExtensionData( 'wikibase_badges' );

		// $actualBadges contains info arrays, these are checked by LanguageLinkBadgeDisplayTest and LangLinkHandlerTest
		$this->assertSame( $expectedBadges, $actualBadges );
	}

	public function testDoContentAlterParserOutput_sitelinkOfNoItem() {
		$content = $this->createMock( Content::class );
		$title = Title::makeTitle( NS_MAIN, 'Plutonium' );

		$parserOutput = $this->newParserOutput( [], [] );
		$handler = $this->newParserOutputUpdateHookHandler( $this->getTestSiteLinkData() );

		$handler->doContentAlterParserOutput( $content, $title, $parserOutput );

		$this->assertNull( $parserOutput->getPageProperty( 'wikibase_item' ) );
		$this->assertSame( -NS_MAIN, $parserOutput->getPageProperty( 'unexpectedUnconnectedPage' ) );

		$this->assertSame( [], $parserOutput->getLanguageLinks() );
		$this->assertSame( [], $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$this->assertNull( $parserOutput->getExtensionData( ParserOutputUsageAccumulator::EXTENSION_DATA_KEY ) );
		$this->assertSame( [], $parserOutput->getExtensionData( 'wikibase_badges' ) );
	}

	public function testDoContentAlterParserOutput_sitelinkInNotWikibaseEnabledNamespace() {
		$content = $this->createMock( Content::class );
		$title = Title::makeTitle( NS_USER, 'Foo' );

		$parserOutput = $this->newParserOutput( [], [] );
		$handler = $this->newParserOutputUpdateHookHandler( $this->getTestSiteLinkDataInNotEnabledNamespace() );

		$handler->doContentAlterParserOutput( $content, $title, $parserOutput );

		$this->assertNull( $parserOutput->getPageProperty( 'wikibase_item' ) );
		$this->assertNull( $parserOutput->getPageProperty( 'unexpectedUnconnectedPage' ) );

		$this->assertSame( [], $parserOutput->getLanguageLinks() );
		$this->assertNull( $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$this->assertNull( $parserOutput->getExtensionData( ParserOutputUsageAccumulator::EXTENSION_DATA_KEY ) );
		$this->assertNull( $parserOutput->getExtensionData( 'wikibase_badges' ) );
	}

	public function testGivenSitelinkHasStatementWithUnknownEntityType_linkDataIsAddedNormally() {
		$itemId = 'Q555';
		$siteLink = new SiteLink( 'enwiki', 'Foobarium' );

		$namespaceChecker = $this->newNamespaceChecker();

		$mockRepo = new MockRepository();
		$item = new Item(
			new ItemId( $itemId ),
			null,
			new SiteLinkList( [ $siteLink ] ),
			new StatementList(
				new Statement(
					new PropertyValueSnak(
						new NumericPropertyId( 'P100' ),
						new UnmappedEntityIdValue( 'X808' )
					)
				)
			)
		);
		$mockRepo->putEntity( $item );

		$langLinkHandlerFactory = $this->newLangLinkHandlerFactory( $namespaceChecker, $mockRepo );

		$handler = new ParserOutputUpdateHookHandler(
			$langLinkHandlerFactory,
			$namespaceChecker,
			$this->newParserOutputDataUpdater( $mockRepo, [ $itemId => [ $siteLink ] ] ),
			$this->newUsageAccumulatorFactory()
		);

		$content = $this->createMock( Content::class );
		$title = Title::makeTitle( NS_MAIN, 'Foobarium' );

		$parserOutput = $this->newParserOutput( [], [] );

		$handler->doContentAlterParserOutput( $content, $title, $parserOutput );

		$this->assertEquals( $itemId, $parserOutput->getPageProperty( 'wikibase_item' ) );
		$this->assertNull( $parserOutput->getPageProperty( 'unexpectedUnconnectedPage' ) );
	}

	private function assertLanguageLinks( $links, ParserOutput $parserOutput ) {
		$this->assertIsArray( $links );

		$actualLinks = $parserOutput->getLanguageLinks();

		foreach ( $links as $link ) {
			$this->assertContains( $link, $actualLinks, 'LanguageLink: ' );
		}

		$this->assertSameSize( $links, $actualLinks, 'Unmatched languageLinks!' );
	}

	private function assertSisterLinks( $expectedLinks, $actualLinks ) {
		if ( !is_array( $expectedLinks ) ) {
			$this->assertEquals( $expectedLinks, $actualLinks );
			return;
		}

		$this->assertSameSize( $expectedLinks, $actualLinks, 'SisterLinks' );

		$actual = reset( $actualLinks );
		foreach ( $expectedLinks as $expected ) {
			$this->assertEquals( $expected, $actual, 'SisterLink: ' );
			$actual = next( $actualLinks );
		}
	}

	private function newNamespaceChecker() {
		$settings = $this->newSettings();

		$namespaces = $settings->getSetting( 'namespaces' );
		$namespaceChecker = new NamespaceChecker( [], $namespaces );

		return $namespaceChecker;
	}

	private function newParserOutputDataUpdater( MockRepository $mockRepo, array $siteLinkData ) {
		$settings = $this->newSettings();

		return new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( $settings, $mockRepo, $siteLinkData ),
			$mockRepo,
			$mockRepo,
			$this->newUsageAccumulatorFactory(),
			$settings->getSetting( 'siteGlobalID' )
		);
	}

	private function newLangLinkHandlerFactory( $namespaceChecker, $mockRepo ) {
		$settings = $this->newSettings();

		return new LangLinkHandlerFactory(
			$this->getBadgeDisplay(),
			$namespaceChecker,
			$mockRepo,
			$mockRepo,
			$this->getSiteLookup(),
			$this->createMock( HookContainer::class ),
			new NullLogger(),
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'languageLinkAllowedSiteGroups' )
		);
	}

}
