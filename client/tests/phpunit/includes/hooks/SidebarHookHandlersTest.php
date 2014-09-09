<?php

namespace Wikibase\Client\Test\Hooks;

use FauxRequest;
use Language;
use MediaWikiSite;
use OutputPage;
use Parser;
use ParserOptions;
use ParserOutput;
use RequestContext;
use Site;
use SiteStore;
use StripState;
use Title;
use Wikibase\Client\ClientSiteLinkLookup;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\SidebarHookHandlers;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\InterwikiSorter;
use Wikibase\LangLinkHandler;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\NamespaceChecker;
use Wikibase\Settings;
use Wikibase\SettingsArray;
use Wikibase\Test\MockRepository;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Client\Hooks\SidebarHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SidebarHookHandlersTest extends \MediaWikiTestCase {

	/**
	 * @param string $globalId
	 * @param string $group
	 * @param $language
	 *
	 * @return Site
	 */
	private function newSite( $globalId, $group, $language ) {
		$site = new MediaWikiSite();
		$site->setGlobalId( $globalId );
		$site->setGroup( $group );
		$site->setLanguageCode( $language );
		$site->addNavigationId( $language );
		$site->setPagePath( 'wiki/' );
		$site->setFilePath( 'w/' );
		$site->setLinkPath( 'http://' . $globalId . '.test.com/wiki/$1' );

		return $site;
	}

	/**
	 * @return SiteStore
	 */
	private function getSiteStore() {
		$siteStore = new MockSiteStore( array(
			$this->newSite( 'wikidatawiki', 'wikidata', 'en' ),
			$this->newSite( 'commonswiki', 'commons', 'en' ),
			$this->newSite( 'enwiki', 'wikipedia', 'en' ),
			$this->newSite( 'dewiki', 'wikipedia', 'de' ),
		) );

		return $siteStore;
	}

	private function getBadgeItem() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q17' ) );
		$item->setLabel( 'de', 'exzellent' );
		$item->setLabel( 'en', 'featured' );

		return $item;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getSiteLinkLookup() {
		$lookup = $this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' );

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

		$q1 = new ItemId( 'Q1' );

		$items = array(
			'dewiki:Sauerstoff' => $q1,
			'enwiki:Oxygen' => $q1,
		);

		$lookup->expects( $this->any() )
			->method( 'getSiteLinksForItem' )
			->will( $this->returnCallback( function( ItemId $itemId ) use ( $links ) {
				$key = $itemId->getSerialization();
				return isset( $links[$key] ) ? $links[$key] : array();
			} ) );

		$lookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnCallback( function( SiteLink $link ) use ( $items ) {
				$key = $link->getSiteId() . ':' . $link->getPageName();
				return isset( $items[$key] ) ? $items[$key] : null;
			} ) );

		return $lookup;
	}

	/**
	 * @param array $settings
	 *
	 * @return Settings
	 */
	private function newSettings( array $settings ) {
		$defaults = array(
			'sort' => 'code',
			'sortPrepend' => array(),
			'interwikiSortOrders' => array( 'alphabetic' => array(
				'ar', 'de', 'en', 'sv', 'zh'
			) ),
			'siteGlobalid' => 'enwiki',
			'languageLinkSiteGroup' => 'wikipedia',
			'namespaces' => array( NS_MAIN, NS_CATEGORY ),
			'alwaysSort' => false,
			'otherProjectsLinks' => array( 'commonswiki' ),
		);

		return new SettingsArray( array_merge( $defaults, $settings ) );
	}

	private function newSidebarHookHandlers( array $settings = array() ) {
		$en = Language::factory( 'en' );
		$settings = $this->newSettings( $settings );

		$siteId = $settings->getSetting( 'siteGlobalid' );
		$siteGroup = $settings->getSetting( 'languageLinkSiteGroup' );
		$namespaces = $settings->getSetting( 'namespaces' );
		$otherProjectIds = $settings->getSetting( 'otherProjectsLinks' );

		$namespaceChecker = new NamespaceChecker( array(), $namespaces );
		$siteLinkLookup = $this->getSiteLinkLookup();
		$siteStore = $this->getSiteStore();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $this->getBadgeItem() );

		$clientSiteLinkLookup = new ClientSiteLinkLookup(
			$siteId,
			$siteLinkLookup,
			$entityLookup
		);

		$otherProjectsSidebarGenerator = new OtherProjectsSidebarGenerator(
			$siteId,
			$siteLinkLookup,
			$siteStore,
			$otherProjectIds
		);

		$langLinkHandler = new LangLinkHandler(
			$otherProjectsSidebarGenerator,
			$siteId,
			$namespaceChecker,
			$siteLinkLookup,
			$siteStore,
			$siteGroup
		);

		$badgeDisplay = new LanguageLinkBadgeDisplay(
			$clientSiteLinkLookup,
			$entityLookup,
			$siteStore,
			array( 'Q17' => 'featured' ),
			$en
		);

		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		return new SidebarHookHandlers(
			$namespaceChecker,
			$langLinkHandler,
			$badgeDisplay,
			$interwikiSorter,
			$settings->getSetting( 'alwaysSort' )
		);

	}

	private function newParser( Title $title, array $pageProps, array $extensionData ) {
		$popt = new ParserOptions();
		$parser = new Parser();

		$parser->startExternalParse( $title, $popt, Parser::OT_HTML );

		$parserOutput = $parser->getOutput();
		$this->primeParserOutput( $parserOutput, $pageProps, $extensionData );

		return $parser;
	}

	private function primeParserOutput( ParserOutput $parserOutput, array $pageProps, array $extensionData ) {
		foreach ( $pageProps as $name => $value ) {
			$parserOutput->setProperty( $name, $value );
		}

		foreach ( $extensionData as $key => $value ) {
			$parserOutput->setExtensionData( $key, $value );
		}
	}

	public function parserAfterParseProvider() {
		$commonsOxygen = array(
			'msg' => 'wikibase-otherprojects-commons',
			'class' => 'wb-otherproject-link wb-otherproject-commons',
			'href' => 'http://commonswiki.test.com/wiki/Oxygen',
			'hreflang' => 'en',
		);

		return array(
			'repo-links' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array(),
				array( 'de:Sauerstoff' ),
				array( $commonsOxygen ),
			),

			'noexternallanglinks=*' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array( 'noexternallanglinks' => serialize( array( '*' ) ) ),
				array(),
				array( $commonsOxygen ),
			),

			'noexternallanglinks=de' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array( 'noexternallanglinks' => serialize( array( 'de' ) ) ),
				array(),
				array( $commonsOxygen ),
			),

			'noexternallanglinks=ja' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array( 'noexternallanglinks' => serialize( array( 'ja' ) ) ),
				array( 'de:Sauerstoff' ),
				array( $commonsOxygen ),
			),

			'no-item' => array(
				Title::makeTitle( NS_MAIN, 'Plutonium' ),
				null,
				array(),
				array(),
				array(),
			),

			'ignored-namespace' => array(
				Title::makeTitle( NS_USER, 'Foo' ),
				null,
				array(),
				array(),
				null,
			),
		);
	}

	/**
	 * @dataProvider parserAfterParseProvider
	 */
	public function testDoParserAfterParse(
		Title $title,
		$expectedItem,
		array $pagePropsBefore,
		array $expectedLanguageLinks = null,
		array $expectedSisterLinks = null
	) {
		$parser = $this->newParser( $title, $pagePropsBefore, array() );
		$handler = $this->newSidebarHookHandlers();

		$text = '';
		$stripState = new StripState( 'x' );

		$handler->doParserAfterParse( $parser, $text, $stripState );

		$parserOutput = $parser->getOutput();
		$this->assertEquals( $expectedItem, $parserOutput->getProperty( 'wikibase_item' ) );
		$this->assertLanguageLinks( $expectedLanguageLinks, $parserOutput );
		$this->assertSisterLinks( $expectedSisterLinks, $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );
	}

	public function testDoOutputPageParserOutput() {
		$title = Title::makeTitle( NS_MAIN, 'Oxygen' );

		$sisterLinks = array(
			array(
				'msg' => 'wikibase-otherprojects-test',
				'class' => 'wb-otherproject-link wb-otherproject-test',
				'href' => 'http://acme.tests.com/wiki/Foo'
			),
		);

		$pageProps = array(
			'noexternallanglinks' => serialize( array( '*' ) ),
			'wikibase_item' => 'Q1',
		);

		$extData = array(
			'wikibase-otherprojects-sidebar' => $sisterLinks,
		);

		$outputProps = array(
			'noexternallanglinks' => array( '*' ),
			'wikibase_item' => 'Q1',
			'wikibase-otherprojects-sidebar' => $sisterLinks,
		);

		$handler = $this->newSidebarHookHandlers();

		$parserOutput = new ParserOutput();

		$context = new RequestContext( new FauxRequest() );
		$outputPage = new OutputPage( $context );
		$outputPage->setTitle( $title );

		$this->primeParserOutput( $parserOutput, $pageProps, $extData );

		$handler->doOutputPageParserOutput( $outputPage, $parserOutput );

		$this->assertOutputPageProperties( $outputProps, $outputPage );
	}

	private function assertOutputPageProperties( $props, OutputPage $outputPage ) {
		$this->assertInternalType( 'array', $props );

		foreach ( $props as $key => $value ) {
			$this->assertEquals( $value, $outputPage->getProperty( $key ), 'OutputProperty: ' . $key );
		}
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
