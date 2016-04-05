<?php

namespace Wikibase\Client\Tests\Hooks;

use HashSiteStore;
use Language;
use MediaWikiSite;
use MediaWikiTestCase;
use ParserOutput;
use Site;
use SiteStore;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\ParserOutputUpdateHookHandlers;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\Term;
use Wikibase\InterwikiSorter;
use Wikibase\LangLinkHandler;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\NamespaceChecker;
use Wikibase\Settings;
use Wikibase\SettingsArray;
use Wikibase\Test\MockRepository;

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
	 * @return SiteStore
	 */
	private function getSiteStore() {
		$siteStore = new HashSiteStore( array(
			$this->newSite( 'wikidatawiki', 'wikidata', 'en' ),
			$this->newSite( 'commonswiki', 'commons', 'en' ),
			$this->newSite( 'enwiki', 'wikipedia', 'en' ),
			$this->newSite( 'dewiki', 'wikipedia', 'de' ),
		) );

		return $siteStore;
	}

	private function getBadgeItem() {
		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'de', 'exzellent' );
		$item->setLabel( 'en', 'featured' );

		return $item;
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
	 * @param array $settings
	 *
	 * @return Settings
	 */
	private function newSettings( array $settings ) {
		$defaults = array(
			'sort' => 'code',
			'sortPrepend' => [],
			'interwikiSortOrders' => array( 'alphabetic' => array(
				'ar', 'de', 'en', 'sv', 'zh'
			) ),
			'siteGlobalID' => 'enwiki',
			'languageLinkSiteGroup' => 'wikipedia',
			'namespaces' => array( NS_MAIN, NS_CATEGORY ),
			'alwaysSort' => false,
			'otherProjectsLinks' => array( 'commonswiki' ),
			'otherProjectsLinksBeta' => true,
			'otherProjectsLinksByDefault' => false,
		);

		return new SettingsArray( array_merge( $defaults, $settings ) );
	}

	private function newParserOutputUpdateHookHandlers( array $settings = [] ) {
		$badgeId = $this->getBadgeItem()->getId();

		$links = array(
			'Q1' => array(
				new SiteLink( 'dewiki', 'Sauerstoff', array( $badgeId ) ),
				new SiteLink( 'enwiki', 'Oxygen' ),
				new SiteLink( 'commonswiki', 'Oxygen' ),
			),
			'Q7' => array(
				new SiteLink( 'dewiki', 'User:Foo' ),
				new SiteLink( 'enwiki', 'User:Foo' ),
				new SiteLink( 'commonswiki', 'User:Foo' ),
			),
		);

		$settings = $this->newSettings( $settings );

		$namespaces = $settings->getSetting( 'namespaces' );
		$namespaceChecker = new NamespaceChecker( [], $namespaces );

		$mockRepo = $this->getMockRepo( $links );
		$mockRepo->putEntity( $this->getBadgeItem() );

		$parserOutputDataUpdater = new ClientParserOutputDataUpdater(
			$this->getOtherProjectsSidebarGeneratorFactory( $settings, $mockRepo ),
			$mockRepo,
			$mockRepo,
			$settings->getSetting( 'siteGlobalID' )
		);

		$langLinkHandler = new LangLinkHandler(
			$this->getBadgeDisplay(),
			$namespaceChecker,
			$mockRepo,
			$mockRepo,
			$this->getSiteStore(),
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'languageLinkSiteGroup' )
		);

		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		return new ParserOutputUpdateHookHandlers(
			$namespaceChecker,
			$langLinkHandler,
			$parserOutputDataUpdater,
			$interwikiSorter,
			$settings->getSetting( 'alwaysSort' )
		);
	}

	private function getBadgeDisplay() {
		$labelDescriptionLookup = $this->getMockBuilder( LabelDescriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( new Term( 'en', 'featured' ) ) );

		return new LanguageLinkBadgeDisplay(
			$labelDescriptionLookup,
			array( 'Q17' => 'featured' ),
			Language::factory( 'en' )
		);
	}

	private function getOtherProjectsSidebarGeneratorFactory(
		SettingsArray $settings,
		SiteLinkLookup $siteLinkLookup
	) {
		return new OtherProjectsSidebarGeneratorFactory(
			$settings,
			$siteLinkLookup,
			$this->getSiteStore()
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
		$commonsOxygen = array(
			'msg' => 'wikibase-otherprojects-commons',
			'class' => 'wb-otherproject-link wb-otherproject-commons',
			'href' => 'http://commonswiki.test.com/wiki/Oxygen',
			'hreflang' => 'en',
		);

		$badgesQ1 = array(
			'class' => 'badge-Q17 featured',
			'label' => 'featured',
		);

		return array(
			'repo-links' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				[],
				array( 'de:Sauerstoff' ),
				array( $commonsOxygen ),
				array( 'de' => $badgesQ1 ),
			),

			'noexternallanglinks=*' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array( 'noexternallanglinks' => serialize( array( '*' ) ) ),
				[],
				array( $commonsOxygen ),
				null,
			),

			'noexternallanglinks=de' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array( 'noexternallanglinks' => serialize( array( 'de' ) ) ),
				[],
				array( $commonsOxygen ),
				[],
			),

			'noexternallanglinks=ja' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array( 'noexternallanglinks' => serialize( array( 'ja' ) ) ),
				array( 'de:Sauerstoff' ),
				array( $commonsOxygen ),
				array( 'de' => $badgesQ1 ),
			),
		);
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
		$handler = $this->newParserOutputUpdateHookHandlers();

		$handler->doContentAlterParserOutput( $title, $parserOutput );

		$expectedUsage = array(
			new EntityUsage(
				new ItemId( $expectedItem ),
				EntityUsage::SITELINK_USAGE
			)
		);

		$this->assertEquals( $expectedItem, $parserOutput->getProperty( 'wikibase_item' ) );
		$this->assertLanguageLinks( $expectedLanguageLinks, $parserOutput );
		$this->assertSisterLinks( $expectedSisterLinks, $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$actualUsage = $parserOutput->getExtensionData( 'wikibase-entity-usage' );

		$this->assertEquals( array_values( $expectedUsage ), array_values( $actualUsage ) );

		$actualBadges = $parserOutput->getExtensionData( 'wikibase_badges' );

		// $actualBadges contains info arrays, these are checked by LanguageLinkBadgeDisplayTest and LangLinkHandlerTest
		$this->assertSame( $expectedBadges, $actualBadges );
	}

	public function parserAfterParseProvider_noItem() {
		return array(
			'no-item' => array(
				Title::makeTitle( NS_MAIN, 'Plutonium' ),
			),
			'ignored-namespace' => array(
				Title::makeTitle( NS_USER, 'Foo' ),
			),
		);
	}

	/**
	 * @dataProvider parserAfterParseProvider_noItem
	 */
	public function testDoContentAlterParserOutput_noItem( Title $title ) {
		$parserOutput = $this->newParserOutput( [], [] );
		$handler = $this->newParserOutputUpdateHookHandlers();

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
