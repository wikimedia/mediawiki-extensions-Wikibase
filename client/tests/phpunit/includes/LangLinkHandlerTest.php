<?php

namespace Wikibase\Client\Tests;

use HashSiteStore;
use ParserOutput;
use Site;
use TestSites;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\LangLinkHandler;
use Wikibase\NamespaceChecker;
use Wikibase\NoLangLinkHandler;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\LangLinkHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class LangLinkHandlerTest extends \MediaWikiTestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepo = null;

	/**
	 * @var LangLinkHandler
	 */
	private $langLinkHandler;

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

	protected function setUp() {
		parent::setUp();

		$this->langLinkHandler = $this->getLangLinkHandler();
	}

	/**
	 * @return LangLinkHandler
	 */
	private function getLangLinkHandler() {
		$this->mockRepo = new MockRepository();

		foreach ( $this->getItems() as $item ) {
			$this->mockRepo->putEntity( $item );
		}

		$siteStore = new HashSiteStore( TestSites::getSites() );

		return new LangLinkHandler(
			$this->getLanguageLinkBadgeDisplay(),
			new NamespaceChecker( array( NS_TALK ) ),
			$this->mockRepo,
			$this->mockRepo,
			$siteStore,
			'srwiki',
			'wikipedia'
		);
	}

	/**
	 * @return LanguageLinkBadgeDisplay
	 */
	private function getLanguageLinkBadgeDisplay() {
		$badgeDisplay = $this->getMockBuilder( LanguageLinkBadgeDisplay::class )
			->disableOriginalConstructor()
			->getMock();

		$badgeDisplay->expects( $this->any() )
			->method( 'attachBadgesToOutput' )
			->will( $this->returnCallback( function ( array $siteLinks, ParserOutput $parserOutput ) {
				$badges = $this->linksToBadges( $siteLinks );
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

			if ( empty( $badges ) ) {
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

	public function provideGetEntityLinks() {
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
	public function testGetEntityLinks( $title, array $expectedLinks ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		$links = array();

		foreach ( $this->langLinkHandler->getEntityLinks( $title ) as $link ) {
			$links[$link->getSiteId()] = $link->getPageName();
		}

		$this->assertArrayEquals( $expectedLinks, $links, false, true );
	}

	public function provideGetNoExternalLangLinks() {
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

	protected function makeParserOutput( array $langLinks, array $noExternalLangLinks = array() ) {
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
	public function testGetNoExternalLangLinks( array $noExternalLangLinks ) {
		$out = $this->makeParserOutput( array(), $noExternalLangLinks );
		$nel = $this->langLinkHandler->getNoExternalLangLinks( $out );

		$this->assertEquals( $noExternalLangLinks, $nel );
	}

	public function provideExcludeRepoLinks() {
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

	public function provideUseRepoLinks() {
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
	public function testUseRepoLinks( $title, array $noExternalLangLinks, $expected ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
			$title->resetArticleID( 1 );
		}

		$out = $this->makeParserOutput( array(), $noExternalLangLinks );

		$useRepoLinks = $this->langLinkHandler->useRepoLinks( $title, $out );

		$this->assertEquals( $expected, $useRepoLinks, "use repository links" );
	}

	public function provideGetEffectiveRepoLinks() {
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
	public function testGetEffectiveRepoLinks(
		$title,
		array $langLinks,
		array $noExternalLangLinks,
		array $expectedLinks
	) {
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
	private function getPlainLinks( array $links ) {
		$flat = array();

		foreach ( $links as $link ) {
			$key = $link->getSiteId();
			$flat[$key] = $link->getPageName();
		}

		return $flat;
	}

	public function provideAddLinksFromRepository() {
		$cases = $this->provideGetEffectiveRepoLinks();

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
			$langLinks = $this->mapToLinks( $case[1] );
			$expectedLinks = $this->mapToLinks( $case[3] );

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
	public function testAddLinksFromRepository(
		$title,
		array $langLinks,
		array $noExternalLangLinks,
		array $expectedLinks,
		array $expectedBadges
	) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		$out = $this->makeParserOutput( $langLinks, $noExternalLangLinks );

		$this->langLinkHandler->addLinksFromRepository( $title, $out );

		$this->assertArrayEquals( $expectedLinks, $out->getLanguageLinks(), false, false );
		$this->assertArrayEquals( $expectedBadges, $out->getExtensionData( 'wikibase_badges' ), false, true );
	}

	protected function mapToLinks( $map ) {
		$links = array();

		foreach ( $map as $wiki => $page ) {
			$lang = preg_replace( '/wiki$/', '', $wiki );
			$links[] = "$lang:$page";
		}

		return $links;
	}

	public function provideFilterRepoLinksByGroup() {
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
	public function testFilterRepoLinksByGroup(
		array $repoLinks,
		array $allowedGroups,
		array $expectedLinks
	) {
		$actualLinks = $this->langLinkHandler->filterRepoLinksByGroup( $repoLinks, $allowedGroups );

		$this->assertEquals( $expectedLinks, $actualLinks );
	}

	public function provideSuppressRepoLinks() {
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
	public function testSuppressRepoLinks( array $repoLinks, array $nel, array $expectedLinks ) {
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
		$enwiki = new Site();
		$enwiki->setGlobalId( 'enwiki' );
		$enwiki->setLanguageCode( 'en' );

		$bexold = new Site();
		$bexold->setGlobalId( 'be_x_oldwiki' );
		$bexold->setLanguageCode( 'be-x-old' );

		$dewikivoyage = new Site();
		$dewikivoyage->setGlobalId( 'dewikivoyage' );
		$dewikivoyage->setLanguageCode( 'de' );

		return array(
			array( $enwiki, 'en' ),
			array( $bexold, 'be-x-old' ),
			array( $dewikivoyage, 'de' )
		);
	}

}
