<?php

namespace Wikibase\Test;

use MediaWikiSite;
use ParserOutput;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\LangLinkHandler;
use Wikibase\NamespaceChecker;
use Wikibase\NoLangLinkHandler;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;

/**
 * @covers Wikibase\LangLinkHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LangLinkHandlerTest extends \MediaWikiTestCase {

	/* @var MockRepository $mockRepo */
	private $mockRepo;

	/* @var LangLinkHandler $langLinkHandler */
	private $langLinkHandler;

	private function getItems() {
		$items = array();

		$item = Item::newEmpty();
		$item->setId( 1 );
		$item->setLabel( 'en', 'Foo' );
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Foo de' );
		$links->addNewSiteLink( 'enwiki', 'Foo en', array( new ItemId( 'Q17' ) ) );
		$links->addNewSiteLink( 'srwiki', 'Foo sr' );
		$links->addNewSiteLink( 'dewiktionary', 'Foo de word' );
		$links->addNewSiteLink( 'enwiktionary', 'Foo en word' );
		$items[] = $item;

		$item = Item::newEmpty();
		$item->setId( 2 );
		$item->setLabel( 'en', 'Talk:Foo' );
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Talk:Foo de' );
		$links->addNewSiteLink( 'enwiki', 'Talk:Foo en' );
		$links->addNewSiteLink( 'srwiki', 'Talk:Foo sr' );
		$items[] = $item;

		return $items;
	}

	public function setUp() {
		parent::setUp();

		$this->langLinkHandler = $this->getLangLinkHandler( array() );
	}

	private function getLangLinkHandler( array $otherProjects ) {
		$this->mockRepo = new MockRepository();

		foreach ( $this->getItems() as $item ) {
			$this->mockRepo->putEntity( $item );
		}

		$sites = MockSiteStore::newFromTestSites();

		return new LangLinkHandler(
			$this->getOtherProjectsSidebarGenerator( $otherProjects ),
			$this->getLanguageLinkBadgeDisplay(),
			'srwiki',
			new NamespaceChecker( array( NS_TALK ), array() ),
			$this->mockRepo,
			$this->mockRepo,
			$sites,
			'wikipedia'
		);
	}

	/**
	 * @param array $otherProjects
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

	/**
	 * @return LanguageLinkBadgeDisplay
	 */
	private function getLanguageLinkBadgeDisplay() {
		$badgeDisplay = $this->getMockBuilder( 'Wikibase\Client\Hooks\LanguageLinkBadgeDisplay' )
			->disableOriginalConstructor()
			->getMock();

		$this_ = $this;

		$badgeDisplay->expects( $this->any() )
			->method( 'attachBadgesToOutput' )
			->will( $this->returnCallback( function ( array $siteLinks, ParserOutput $parserOutput ) use ( $this_ ) {
				$badges = $this_->linksToBadges( $siteLinks );
				$parserOutput->setExtensionData( 'wikibase_badges', $badges );
			} ) );

		return $badgeDisplay;
	}

	/**
	 * @param SiteLink[] $siteLinks
	 *
	 * @return string[]
	 */
	public function linksToBadges( array $siteLinks ) {
		$badgesByPrefix = array();

		foreach ( $siteLinks as $link ) {
			$badges = $link->getBadges();

			if ( empty( $badges ) )  {
				continue;
			}

			// strip "wiki" suffix
			$key = preg_replace( '/wiki$/', '', $link->getSiteId() );
			$badgesByPrefix[$key] = array_map( function( ItemId $id ) {
				return $id->getSerialization();
			}, $badges );
		}

		return $badgesByPrefix;
	}

	public static function provideGetEntityLinks() {
		return array(
			array( // #0
				'Xoo', // page
				array() // expected links
			),
			array( // #1
				'Foo_sr', // page
				array( // expected links
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetEntityLinks
	 */
	public function testGetEntityLinks( $title, $expectedLinks ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		$links = array();

		foreach ( $this->langLinkHandler->getEntityLinks( $title ) as $link ) {
			$links[$link->getSiteId()] = $link->getPageName();
		}

		$this->assertArrayEquals( $expectedLinks, $links, false, true );
	}

	public static function provideGetNoExternalLangLinks() {
		return array(
			array( // #0
				array()
			),
			array( // #1
				array( '*' )
			),
			array( // #2
				array( 'de' )
			),
			array( // #3
				array( 'xy', 'de', 'en' )
			),
		);
	}

	protected function makeParserOutput( $langLinks, $noExternalLangLinks = array() ) {
		$out = new ParserOutput();
		NoLangLinkHandler::setNoExternalLangLinks( $out, $noExternalLangLinks );

		foreach ( $langLinks as $lang => $link ) {
			$out->addLanguageLink( "$lang:$link" );
		}

		return $out;
	}

	/**
	 * @dataProvider provideGetNoExternalLangLinks
	 */
	public function testGetNoExternalLangLinks( $noExternalLangLinks ) {
		$out = $this->makeParserOutput( array(), $noExternalLangLinks );
		$nel = $this->langLinkHandler->getNoExternalLangLinks( $out );

		$this->assertEquals( $noExternalLangLinks, $nel );
	}

	public static function provideExcludeRepoLinks() {
		return array(
			array( // #0
				array(),
				array(),
				array()
			),
			array( // #1
				array( 'de' ),
				array( 'cs' ),
				array( 'de', 'cs' )
			),
			array(
				array( 'de' ),
				array( '*' ),
				array( 'de', '*' )
			),
			array( // #3
				array( 'xy', 'de', 'en' ),
				array(),
				array( 'xy', 'de', 'en' )
			)
		);
	}

	public static function provideUseRepoLinks() {
		return array(
			array( // #0
				'Foo_sr',
				array(),
				true
			),
			array( // #1
				'Foo_sr',
				array( '*' ),
				false
			),
			array( // #2
				'Foo_sr',
				array( 'de' ),
				true
			),
			array( // #3
				'Talk:Foo_sr',
				array(),
				false
			),
		);
	}

	/**
	 * @dataProvider provideUseRepoLinks
	 */
	public function testUseRepoLinks( $title, $noExternalLangLinks, $expected ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
			$title->resetArticleID( 1 );
		}

		$out = $this->makeParserOutput( array(), $noExternalLangLinks );

		$useRepoLinks = $this->langLinkHandler->useRepoLinks( $title, $out );

		$this->assertEquals( $expected, $useRepoLinks, "use repository links" );
	}

	public static function provideGetEffectiveRepoLinks() {
		return array(
			array( // #0: local overrides remote
				'Foo_sr', // title
				array( // langlinks
					'de' => 'Xoo de',
					'nl' => 'Foo nl',
				),
				array( // noexternallinks
				),
				array( // expectedLinks
					'enwiki' => 'Foo en',
				)
			),
			array( // #1: namespace not covered
				'Talk:Foo_sr', // title
				array( // langlinks
					'de' => 'Talk:Foo de',
					'nl' => 'Talk:Foo nl',
				),
				array( // noexternallinks
				),
				array( // expectedLinks
				)
			),
			array( // #2: disabled
				'Foo_sr', // title
				array( // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				),
				array( // noexternallinks
					'*'
				),
				array( // expectedLinks
				)
			),
			array( // #3: suppressed
				'Foo_sr', // title
				array( // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				),
				array( // noexternallinks
					'en'
				),
				array( // expectedLinks
				)
			),
			array( // #4: suppressed redundantly
				'Foo_sr', // title
				array( // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				),
				array( // noexternallinks
					'de'
				),
				array( // expectedLinks
					'enwiki' => 'Foo en',
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetEffectiveRepoLinks
	 */
	public function testGetEffectiveRepoLinks( $title, $langLinks, $noExternalLangLinks, $expectedLinks ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		$out = $this->makeParserOutput( $langLinks, $noExternalLangLinks );

		$links = $this->langLinkHandler->getEffectiveRepoLinks( $title, $out );
		$links = $this->getPlainLinks( $links );

		$this->assertArrayEquals( $expectedLinks, $links, false, true );
	}

	/**
	 * @param SiteLink[] $links
	 *
	 * @return array
	 */
	private function getPlainLinks( $links ) {
		$flat = array();

		foreach ( $links as $link ) {
			$key = $link->getSiteId();
			$flat[$key] = $link->getPageName();
		}

		return $flat;
	}

	public static function provideAddLinksFromRepository() {
		$cases = self::provideGetEffectiveRepoLinks();


		$badges = array(
			// as defined by getItems()
			'Foo_en' => array(
				'en' => array( 'Q17' ),
			),
			'Foo_sr' => array(
				'en' => array( 'Q17' ),
			),
		);

		foreach ( $cases as $i => $case ) {
			// convert associative array to list of links
			$langLinks = self::mapToLinks( $case[1] );
			$expectedLinks = self::mapToLinks( $case[3] );

			// expect the expected effective links plus the provided language links
			$expectedLinks = array_merge( $expectedLinks, $langLinks );

			if ( !in_array( '*', $case[2] ) ) {
				$expectedBadges = isset( $badges[ $case[0] ] ) ? $badges[ $case[0] ] : array();

				// no badges for languages mentioned in $noExternalLangLinks
				$expectedBadges = array_diff_key( $expectedBadges, array_flip( $case[2] ) );
			} else {
				$expectedBadges = array();
			}

			$cases[$i] = array(
				$case[0],
				$case[1],
				$case[2],
				$expectedLinks,
				$expectedBadges
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider provideAddLinksFromRepository
	 */
	public function testAddLinksFromRepository( $title, $langLinks, $noExternalLangLinks, $expectedLinks, $expectedBadges ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		$out = $this->makeParserOutput( $langLinks, $noExternalLangLinks );

		$langLinkHandler = $this->getLangLinkHandler( array() );
		$langLinkHandler->addLinksFromRepository( $title, $out );

		$this->assertArrayEquals( $expectedLinks, $out->getLanguageLinks(), false, false );
		$this->assertArrayEquals( $expectedBadges, $out->getExtensionData( 'wikibase_badges' ), false, true );
	}

	protected static function mapToLinks( $map ) {
		$links = array();

		foreach ( $map as $wiki => $page ) {
			$lang = preg_replace( '/wiki$/', '', $wiki );
			$links[] = "$lang:$page";
		}

		return $links;
	}

	public static function provideFilterRepoLinksByGroup() {
		return array(
			array( // #0: nothing
				array(), array(), array()
			),
			array( // #1: nothing allowed
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				),
				array(),
				array()
			),
			array( // #2: nothing there
				array(),
				array( 'wikipedia' ),
				array()
			),
			array( // #3: wikipedia only
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				),
				array( 'wikipedia' ),
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
				),
			),
		);
	}

	/**
	 * @dataProvider provideFilterRepoLinksByGroup
	 */
	public function testFilterRepoLinksByGroup( $repoLinks, $allowedGroups, $expectedLinks ) {
		$actualLinks = $this->langLinkHandler->filterRepoLinksByGroup( $repoLinks, $allowedGroups );

		$this->assertEquals( $expectedLinks, $actualLinks );
	}

	public static function provideSuppressRepoLinks() {
		return array(
			array( // #0: nothing
				array(), array(), array()
			),
			array( // #1: nothing allowed
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				),
				array( '*' ),
				array()
			),
			array( // #2: nothing there
				array(),
				array( 'de' ),
				array()
			),
			array( // #3: no de
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'enwiktionary' => 'Foo en word',
				),
				array( 'de' ),
				array(
					'enwiki' => 'Foo en',
					//NOTE: srwiki is removed because that's a self-link
					'enwiktionary' => 'Foo en word',
				),
			),
		);
	}

	/**
	 * @dataProvider provideSuppressRepoLinks
	 */
	public function testSuppressRepoLinks( $repoLinks, $nel, $expectedLinks ) {
		$out = new ParserOutput();
		$out->setProperty( 'noexternallanglinks', serialize( $nel ) );

		$actualLinks = $this->langLinkHandler->suppressRepoLinks( $out, $repoLinks );

		$this->assertEquals( $expectedLinks, $actualLinks );
	}

	/**
	 * @dataProvider getInterwikiCodeFromSiteProvider
	 */
	public function testGetInterwikiCodeFromSite( $site, $expected ) {
		$interwikiCode = $this->langLinkHandler->getInterwikiCodeFromSite( $site );
		$this->assertEquals( $expected, $interwikiCode, 'interwiki code matches' );
	}

	public function getInterwikiCodeFromSiteProvider() {
		$enwiki = MediaWikiSite::newFromGlobalId( 'enwiki' );
		$enwiki->setLanguageCode( 'en' );

		$bexold = MediaWikiSite::newFromGlobalId( 'be_x_oldwiki' );
		$bexold->setLanguageCode( 'be-x-old' );

		$dewikivoyage = MediaWikiSite::newFromGlobalId( 'dewikivoyage' );
		$dewikivoyage->setLanguageCode( 'de' );

		return array(
			array( $enwiki, 'en' ),
			array( $bexold, 'be-x-old' ),
			array( $dewikivoyage, 'de' )
		);
	}

	public function testUpdateItemIdProperty() {
		$langLinkHandler = $this->getLangLinkHandler( array() );

		$parserOutput = new ParserOutput();

		$titleText = 'Foo sr';
		$title = Title::newFromText( $titleText );

		$langLinkHandler->updateItemIdProperty( $title, $parserOutput );
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
		$langLinkHandler = $this->getLangLinkHandler( array() );

		$parserOutput = new ParserOutput();

		$titleText = 'Foo xx';
		$title = Title::newFromText( $titleText );

		$langLinkHandler->updateItemIdProperty( $title, $parserOutput );
		$property = $parserOutput->getProperty( 'wikibase_item' );

		$this->assertEquals( false, $property );
	}

	/**
	 * @dataProvider updateOtherProjectsLinksDataProvider
	 */
	public function testUpdateOtherProjectsLinksData( $expected, $otherProjects, $titleText ) {
		$langLinkHandler = $this->getLangLinkHandler( $otherProjects );

		$parserOutput = new ParserOutput();
		$title = Title::newFromText( $titleText );

		$langLinkHandler->updateOtherProjectsLinksData( $title, $parserOutput );
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
