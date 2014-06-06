<?php

namespace Wikibase\Test;

use MediaWikiSite;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\LangLinkHandler;
use Wikibase\NamespaceChecker;

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
		$item->getSiteLinkList()
			->addNewSiteLink( 'dewiki', 'Foo de' )
			->addNewSiteLink( 'enwiki', 'Foo en' )
			->addNewSiteLink( 'srwiki', 'Foo sr' )
			->addNewSiteLink( 'enwiktionary', 'Foo de word' )
			->addNewSiteLink( 'enwiktionary', 'Foo en word' );
		$items[] = $item;

		$item = Item::newEmpty();
		$item->setId( 2 );
		$item->setLabel( 'en', 'Talk:Foo' );
		$item->getSiteLinkList()
			->addNewSiteLink( 'dewiki', 'Talk:Foo de' )
			->addNewSiteLink( 'enwiki', 'Talk:Foo en' )
			->addNewSiteLink( 'srwiki', 'Talk:Foo sr' );
		$items[] = $item;

		return $items;
	}

	public function setUp() {
		parent::setUp();

		$this->mockRepo = new MockRepository();

		foreach ( $this->getItems() as $item ) {
			$this->mockRepo->putEntity( $item );
		}

		$sites = MockSiteStore::newFromTestSites();

		$this->langLinkHandler = new LangLinkHandler(
			'srwiki',
			new NamespaceChecker( array( NS_TALK ), array() ),
			$this->mockRepo,
			$sites,
			'wikipedia'
		);
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
		$this->langLinkHandler->setNoExternalLangLinks( $out, $noExternalLangLinks );

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

	/**
	 * @dataProvider provideExcludeRepoLinks
	 */
	public function testExcludeRepoLinks( $alreadyExcluded, $toExclude, $expected ) {
		$out = new ParserOutput();
		$this->langLinkHandler->setNoExternalLangLinks( $out, $alreadyExcluded );
		$this->langLinkHandler->excludeRepoLangLinks( $out, $toExclude );
		$nel = $this->langLinkHandler->getNoExternalLangLinks( $out );

		$this->assertEquals( $expected, $nel );
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

		$this->assertArrayEquals( $expectedLinks, $links, false, true );
	}

	public static function provideAddLinksFromRepository() {
		$cases = self::provideGetEffectiveRepoLinks();

		foreach ( $cases as $i => $case ) {
			// convert associative array to list of links
			$langLinks = self::mapToLinks( $case[1] );
			$expectedLinks = self::mapToLinks( $case[3] );

			// expect the expected effective links plus the provided language links
			$expectedLinks = array_merge( $expectedLinks, $langLinks );

			$cases[$i] = array(
				$case[0],
				$case[1],
				$case[2],
				$expectedLinks
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider provideAddLinksFromRepository
	 */
	public function testAddLinksFromRepository( $title, $langLinks, $noExternalLangLinks, $expectedLinks ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		$out = $this->makeParserOutput( $langLinks, $noExternalLangLinks );

		$this->langLinkHandler->addLinksFromRepository( $title, $out );
		$links = $out->getLanguageLinks();

		$this->assertArrayEquals( $expectedLinks, $links, false, false );
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

}
