<?php

namespace Wikibase\Client\Tests\Hooks;

use HashSiteStore;
use Language;
use MediaWikiSite;
use MediaWikiTestCase;
use ParserOutput;
use Site;
use SiteLookup;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\ParserOutputUpdateHookHandlers;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\Term;
use Wikibase\Client\LangLinkHandler;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Settings;
use Wikibase\SettingsArray;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Client\Hooks\ParserOutputUpdateHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ParserOutputUpdateHookHandlersTest extends MediaWikiTestCase {

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
	 * @return Settings
	 */
	private function newSettings() {
		$defaults = [
			'siteGlobalID' => 'enwiki',
			'languageLinkSiteGroup' => 'wikipedia',
			'namespaces' => [ NS_MAIN, NS_CATEGORY ],
			'otherProjectsLinks' => [ 'commonswiki' ],
		];

		return new SettingsArray( $defaults );
	}

	private function newParserOutputUpdateHookHandlers( array $siteLinkData ) {
		$settings = $this->newSettings();

		$namespaces = $settings->getSetting( 'namespaces' );
		$namespaceChecker = new NamespaceChecker( [], $namespaces );

		$mockRepo = $this->getMockRepo( $siteLinkData );
		$mockRepo->putEntity( $this->getBadgeItem() );

		$parserOutputDataUpdater = new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( $settings, $mockRepo, $siteLinkData ),
			$mockRepo,
			$mockRepo,
			$settings->getSetting( 'siteGlobalID' )
		);

		$langLinkHandler = new LangLinkHandler(
			$this->getBadgeDisplay(),
			$namespaceChecker,
			$mockRepo,
			$mockRepo,
			$this->getSiteLookup(),
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'languageLinkSiteGroup' )
		);

		return new ParserOutputUpdateHookHandlers(
			$namespaceChecker,
			$langLinkHandler,
			$parserOutputDataUpdater
		);
	}

	private function getBadgeDisplay() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( new Term( 'en', 'featured' ) ) );

		return new LanguageLinkBadgeDisplay(
			new SidebarLinkBadgeDisplay(
				$labelDescriptionLookup,
				[ 'Q17' => 'featured' ],
				Language::factory( 'en' )
			)
		);
	}

	private function getEntityLookup( array $siteLinkData ) {
		$lookup = new InMemoryEntityLookup();

		foreach ( $siteLinkData as $itemId => $siteLinks ) {
			$item = new Item( new ItemId( 'Q1' ) );
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
			$this->getMock( LabelDescriptionLookup::class ),
			[],
			new Language( 'en' )
		);

		return new OtherProjectsSidebarGeneratorFactory(
			$settings,
			$siteLinkLookup,
			$this->getSiteLookup(),
			$this->getEntityLookup( $siteLinkData ),
			$sidebarLinkBadgeDisplay
		);
	}

	private function newParserOutput( array $pageProps, array $extensionData ) {
		$parserOutput = new ParserOutput();

		foreach ( $pageProps as $name => $value ) {
			$parserOutput->setProperty( $name, $value );
		}

		foreach ( $extensionData as $key => $value ) {
			$parserOutput->setExtensionData( $key, $value );
		}

		return $parserOutput;
	}

	public function testNewFromGlobalState() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$oldSiteGroupValue = $settings->getSetting( 'siteGroup' );
		$settings->setSetting( 'siteGroup', 'NYAN' );

		$handler = ParserOutputUpdateHookHandlers::newFromGlobalState();
		$this->assertInstanceOf( ParserOutputUpdateHookHandlers::class, $handler );

		$settings->setSetting( 'siteGroup', $oldSiteGroupValue );
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
				[ 'noexternallanglinks' => serialize( [ '*' ] ) ],
				[],
				[ $commonsOxygen ],
				null,
			],

			'noexternallanglinks=de' => [
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				[ 'noexternallanglinks' => serialize( [ 'de' ] ) ],
				[],
				[ $commonsOxygen ],
				[],
			],

			'noexternallanglinks=ja' => [
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				[ 'noexternallanglinks' => serialize( [ 'ja' ] ) ],
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
		array $pagePropsBefore,
		array $expectedLanguageLinks,
		array $expectedSisterLinks,
		array $expectedBadges = null
	) {
		$parserOutput = $this->newParserOutput( $pagePropsBefore, [] );
		$handler = $this->newParserOutputUpdateHookHandlers( $this->getTestSiteLinkData() );

		$handler->doContentAlterParserOutput( $title, $parserOutput );

		$expectedUsage = [
			new EntityUsage(
				new ItemId( $expectedItem ),
				EntityUsage::SITELINK_USAGE
			)
		];

		$this->assertEquals( $expectedItem, $parserOutput->getProperty( 'wikibase_item' ) );
		$this->assertLanguageLinks( $expectedLanguageLinks, $parserOutput );
		$this->assertSisterLinks( $expectedSisterLinks, $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$actualUsage = $parserOutput->getExtensionData( 'wikibase-entity-usage' );

		$this->assertEquals( array_values( $expectedUsage ), array_values( $actualUsage ) );

		$actualBadges = $parserOutput->getExtensionData( 'wikibase_badges' );

		// $actualBadges contains info arrays, these are checked by LanguageLinkBadgeDisplayTest and LangLinkHandlerTest
		$this->assertSame( $expectedBadges, $actualBadges );
	}

	public function testDoContentAlterParserOutput_sitelinkOfNoItem() {
		$title = Title::makeTitle( NS_MAIN, 'Plutonium' );

		$parserOutput = $this->newParserOutput( [], [] );
		$handler = $this->newParserOutputUpdateHookHandlers( $this->getTestSiteLinkData() );

		$handler->doContentAlterParserOutput( $title, $parserOutput );

		$this->assertFalse( $parserOutput->getProperty( 'wikibase_item' ) );

		$this->assertEmpty( $parserOutput->getLanguageLinks() );
		$this->assertEmpty( $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$this->assertEmpty( $parserOutput->getExtensionData( 'wikibase-entity-usage' ) );
		$this->assertEmpty( $parserOutput->getExtensionData( 'wikibase_badges' ) );
	}

	public function testDoContentAlterParserOutput_sitelinkInNotWikibaseEnabledNamespace() {
		$title = Title::makeTitle( NS_USER, 'Foo' );

		$parserOutput = $this->newParserOutput( [], [] );
		$handler = $this->newParserOutputUpdateHookHandlers( $this->getTestSiteLinkDataInNotEnabledNamespace() );

		$handler->doContentAlterParserOutput( $title, $parserOutput );

		$this->assertFalse( $parserOutput->getProperty( 'wikibase_item' ) );

		$this->assertEmpty( $parserOutput->getLanguageLinks() );
		$this->assertEmpty( $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$this->assertEmpty( $parserOutput->getExtensionData( 'wikibase-entity-usage' ) );
		$this->assertEmpty( $parserOutput->getExtensionData( 'wikibase_badges' ) );
	}

	private function assertLanguageLinks( $links, ParserOutput $parserOutput ) {
		$this->assertInternalType( 'array', $links );

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

}
