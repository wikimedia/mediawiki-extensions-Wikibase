<?php

namespace Wikibase\Client\Test\Hooks;

use Language;
use MediaWikiSite;
use Parser;
use ParserOptions;
use ParserOutput;
use Site;
use SiteStore;
use StripState;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\ParserAfterParseHookHandler;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
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
 * @covers Wikibase\Client\Hooks\ParserAfterParseHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ParserAfterParseHookHandlerTest extends \MediaWikiTestCase {

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

	private function newParserAfterParseHookHandler( array $settings = array() ) {
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
		$namespaceChecker = new NamespaceChecker( array(), $namespaces );

		$mockRepo = $this->getMockRepo( $links );
		$mockRepo->putEntity( $this->getBadgeItem() );

		$badgeDisplay = new LanguageLinkBadgeDisplay(
			$mockRepo,
			array( 'Q17' => 'featured' ),
			Language::factory( 'en' )
		);

		$langLinkHandler = new LangLinkHandler(
			$this->getOtherProjectsSidebarGeneratorFactory( $settings, $mockRepo ),
			$badgeDisplay,
			$settings->getSetting( 'siteGlobalID' ),
			$namespaceChecker,
			$mockRepo,
			$mockRepo,
			$this->getSiteStore()->getSites(),
			$settings->getSetting( 'languageLinkSiteGroup' )
		);

		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		return new ParserAfterParseHookHandler(
			$namespaceChecker,
			$langLinkHandler,
			$interwikiSorter,
			$settings->getSetting( 'alwaysSort' )
		);

	}

	private function getOtherProjectsSidebarGeneratorFactory(
		SettingsArray $settings,
		SiteLinkLookup $siteLinkLookup
	) {
		return  new OtherProjectsSidebarGeneratorFactory(
			$settings,
			$siteLinkLookup,
            $this->getSiteStore()
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

		$handler = ParserAfterParseHookHandler::newFromGlobalState();
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\ParserAfterParseHookHandler', $handler );

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
				array(),
				array( 'de:Sauerstoff' ),
				array( $commonsOxygen ),
				array( 'de' => $badgesQ1 ),
			),

			'noexternallanglinks=*' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array( 'noexternallanglinks' => serialize( array( '*' ) ) ),
				array(),
				array( $commonsOxygen ),
				null,
			),

			'noexternallanglinks=de' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				'Q1',
				array( 'noexternallanglinks' => serialize( array( 'de' ) ) ),
				array(),
				array( $commonsOxygen ),
				array(),
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
	public function testDoParserAfterParse(
		Title $title,
		$expectedItem,
		$pagePropsBefore,
		$expectedLanguageLinks,
		$expectedSisterLinks,
		$expectedBadges
	) {
		$parser = $this->newParser( $title, $pagePropsBefore, array() );
		$handler = $this->newParserAfterParseHookHandler();

		$handler->doParserAfterParse( $parser );

		$expectedUsage = array(
			new EntityUsage(
				new ItemId( $expectedItem ),
				EntityUsage::SITELINK_USAGE
			)
		);

		$parserOutput = $parser->getOutput();

		$this->assertEquals( $expectedItem, $parserOutput->getProperty( 'wikibase_item' ) );
		$this->assertLanguageLinks( $expectedLanguageLinks, $parserOutput );
		$this->assertSisterLinks( $expectedSisterLinks, $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' ) );

		$actualUsage = $parserOutput->getExtensionData( 'wikibase-entity-usage' );

		$this->assertEquals( array_values( $expectedUsage ), array_values( $actualUsage ) );

		$actualBadges = $parserOutput->getExtensionData( 'wikibase_badges' );

		// $actualBadges contains info arrays, these are checked by LanguageLinkBadgeDisplayTest and LangLinkHandlerTest
		$this->assertEquals( $expectedBadges , $actualBadges );
	}

	/**
	 * @see https://bugzilla.wikimedia.org/show_bug.cgi?id=71772
	 */
	public function testOnParserAfterParse_withoutParameters() {
		$this->assertTrue( ParserAfterParseHookHandler::onParserAfterParse() );
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
	public function testDoParserAfterParse_noItem( Title $title ) {

		$parser = $this->newParser( $title, array(), array() );
		$handler = $this->newParserAfterParseHookHandler();

		$text = '';
		$stripState = new StripState( 'x' );
		$handler->doParserAfterParse( $parser, $text, $stripState );

		$parserOutput = $parser->getOutput();
		$this->assertEquals( null, $parserOutput->getProperty( 'wikibase_item' ) );

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
