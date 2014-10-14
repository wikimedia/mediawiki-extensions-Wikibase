<?php

namespace Wikibase\Client\Test\Hooks;

use FauxRequest;
use IContextSource;
use Language;
use MediaWikiSite;
use OutputPage;
use Parser;
use ParserOptions;
use ParserOutput;
use RequestContext;
use Site;
use SiteStore;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\SidebarHookHandlers;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\InterwikiSorter;
use Wikibase\LangLinkHandler;
use Wikibase\Lib\Store\TermLookupService;
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
	 * @param ItemId $id
	 * @param SiteLink[] $links
	 *
	 * @return Item
	 */
	private function newItem( ItemId $id, $links ) {
		$item = Item::newEmpty();
		$item->setId( $id );

		foreach ( $links as $link ) {
			$item->addSiteLink( $link );
		}

		return $item;
	}

	/**
	 * @param array[] $links
	 *
	 * @return MockRepository
	 */
	private function getMockRepo( $links ) {
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
			'sortPrepend' => array(),
			'interwikiSortOrders' => array( 'alphabetic' => array(
				'ar', 'de', 'en', 'sv', 'zh'
			) ),
			'siteGlobalid' => 'enwiki',
			'languageLinkSiteGroup' => 'wikipedia',
			'namespaces' => array( NS_MAIN, NS_CATEGORY ),
			'alwaysSort' => false,
			'otherProjectsLinks' => array( 'commonswiki' ),
			'otherProjectsLinksBeta' => true,
			'otherProjectsLinksByDefault' => false,
		);

		return new SettingsArray( array_merge( $defaults, $settings ) );
	}

	/**
	 * @param array $projects
	 *
	 * @return OtherProjectsSidebarGenerator
	 */
	private function getSidebarGenerator( array $projects ) {
		$sidebarGenerator = $this->getMockBuilder( 'Wikibase\Client\Hooks\OtherProjectsSidebarGenerator' )
			->disableOriginalConstructor()
			->getMock();

		$sidebarGenerator->expects( $this->any() )
			->method( 'buildProjectLinkSidebar' )
			->will( $this->returnValue( $projects ) );

		return $sidebarGenerator;
	}

	private function newSidebarHookHandlers( array $settings = array() ) {
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

		$en = Language::factory( 'en' );
		$settings = $this->newSettings( $settings );

		$siteId = $settings->getSetting( 'siteGlobalid' );
		$siteGroup = $settings->getSetting( 'languageLinkSiteGroup' );
		$namespaces = $settings->getSetting( 'namespaces' );
		$otherProjectIds = $settings->getSetting( 'otherProjectsLinks' );

		$namespaceChecker = new NamespaceChecker( array(), $namespaces );
		$siteStore = $this->getSiteStore();

		$mockRepo = $this->getMockRepo( $links );
		$mockRepo->putEntity( $this->getBadgeItem() );

		$otherProjectsSidebarGenerator = new OtherProjectsSidebarGenerator(
			$siteId,
			$mockRepo,
			$siteStore,
			$otherProjectIds
		);

		$badgeDisplay = new LanguageLinkBadgeDisplay(
			new TermLookupService( $mockRepo ),
			array( 'Q17' => 'featured' ),
			$en
		);

		$langLinkHandler = new LangLinkHandler(
			$otherProjectsSidebarGenerator,
			$badgeDisplay,
			$siteId,
			$namespaceChecker,
			$mockRepo,
			$mockRepo,
			$siteStore,
			$siteGroup
		);

		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		$sidebarGenerator = $this->getSidebarGenerator( array( 'dummy' => 'xyz' ) );

		return new SidebarHookHandlers(
			$namespaceChecker,
			$langLinkHandler,
			$badgeDisplay,
			$interwikiSorter,
			$sidebarGenerator,
			$settings
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

	public function testNewFromGlobalState() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$oldSiteGroupValue = $settings->getSetting( 'siteGroup' );
		$settings->setSetting( 'siteGroup', 'NYAN' );

		$handler = SidebarHookHandlers::newFromGlobalState();
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\SidebarHookHandlers', $handler );

		$settings->setSetting( 'siteGroup', $oldSiteGroupValue );
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
				array( 'de' => array( new ItemId( 'Q17' ) ) ),
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
				array( 'de' => array( new ItemId( 'Q17' ) ) ),
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
		array $expectedSisterLinks = null,
		array $expectedBadges = null
	) {
		$parser = $this->newParser( $title, $pagePropsBefore, array() );
		$handler = $this->newSidebarHookHandlers();

		$handler->doParserAfterParse( $parser );

		$parserOutput = $parser->getOutput();
		$this->assertEquals( $expectedItem, $parserOutput->getProperty( 'wikibase_item' ) );
		$this->assertLanguageLinks( $expectedLanguageLinks, $parserOutput );
		$this->assertSisterLinks( $expectedSisterLinks, $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$actualBadges = $parserOutput->getExtensionData( 'wikibase_badges' );

		if ( $expectedBadges === null ) {
			$this->assertEmpty( $actualBadges );
		} else {
			// $actualBadges contains info arrays, these are checked by LanguageLinkBadgeDisplayTest and LangLinkHandlerTest
			$this->assertEquals( array_keys( $expectedBadges ) , array_keys( $actualBadges ) );
		}
	}

	/**
	 * @see https://bugzilla.wikimedia.org/show_bug.cgi?id=71772
	 */
	public function testOnParserAfterParse_withoutParameters() {
		$this->assertTrue( SidebarHookHandlers::onParserAfterParse() );
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

	public function testDoSkinTemplateGetLanguageLink() {
		$badges = array(
			'en' => array(
				'class' => 'badge-Q3',
				'label' => 'Lesenswerter Artikel',
			)
		);

		$link = array(
			'href' => 'http://acme.com',
			'class' => 'foo',
		);

		$expected = array(
			'href' => 'http://acme.com',
			'class' => 'foo badge-Q3',
			'itemtitle' => 'Lesenswerter Artikel',
		);

		$languageLinkTitle = Title::makeTitle( NS_MAIN, 'Test', '', 'en' );

		$dummy = Title::makeTitle( NS_MAIN, 'Dummy' );

		$context = new RequestContext( new FauxRequest() );
		$output = new OutputPage( $context );
		$output->setProperty( 'wikibase_badges', $badges );

		$handler = $this->newSidebarHookHandlers();
		$handler->doSkinTemplateGetLanguageLink( $link, $languageLinkTitle, $dummy, $output );

		$this->assertEquals( $expected, $link );
	}

	/**
	 * @return \Skin
	 */
	private function newSkin( IContextSource $context ) {
		$skin = $this->getMockBuilder( 'Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		return $skin;
	}

	/**
	 * Call the doSidebarBeforeOutput() function on the SidebarHookHandlers object under test.
	 *
	 * @param boolean $enabled
	 * @param array|null $projects A list of projects
	 *
	 * @return array The resulting sidebar array
	 */
	private function callDoSidebarBeforeOutput( $enabled, $projects ) {
		$title = Title::makeTitle( NS_MAIN, 'Oxygen' );

		$context = new RequestContext( new FauxRequest() );

		$output = new OutputPage( $context );
		$output->setTitle( $title );
		$output->setProperty( 'wikibase-otherprojects-sidebar', $projects );

		$context->setOutput( $output );
		$skin = $this->newSkin( $context );

		$sidebar = array();

		$handler = $this->newSidebarHookHandlers( array(
			'otherProjectsLinksByDefault' => $enabled
		) );

		$handler->doSidebarBeforeOutput( $skin, $sidebar );
		return $sidebar;
	}

	public function testDoSidebarBeforeOutput() {
		$projects = array( 'foo' => 'bar' );
		$sidebar = $this->callDoSidebarBeforeOutput( true, $projects );

		$this->assertArrayHasKey( 'wikibase-otherprojects', $sidebar );
		$this->assertEquals( $sidebar['wikibase-otherprojects'], $projects );
	}

	public function testDoSidebarBeforeOutput_empty() {
		$projects = array();
		$sidebar = $this->callDoSidebarBeforeOutput( true, $projects );

		$this->assertArrayNotHasKey( 'wikibase-otherprojects', $sidebar );
	}

	public function testDoSidebarBeforeOutput_disabled() {
		$projects = array( 'foo' => 'bar' );
		$sidebar = $this->callDoSidebarBeforeOutput( false, $projects );

		$this->assertArrayNotHasKey( 'wikibase-otherprojects', $sidebar );
	}

	public function testDoSidebarBeforeOutput_generate() {
		// If no sidebar is set, it should be generated on the fly
		$sidebar = $this->callDoSidebarBeforeOutput( true, null );

		$this->assertArrayHasKey( 'wikibase-otherprojects', $sidebar );
		$this->assertNotEmpty( $sidebar );
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
