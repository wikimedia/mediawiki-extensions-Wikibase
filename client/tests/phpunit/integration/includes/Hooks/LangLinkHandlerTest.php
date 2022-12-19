<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use HashSiteStore;
use MediaWiki\HookContainer\HookContainer;
use MediaWikiIntegrationTestCase;
use ParserOutput;
use Psr\Log\NullLogger;
use Site;
use TestSites;
use Title;
use Wikibase\Client\Hooks\LangLinkHandler;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\NoLangLinkHandler;
use Wikibase\Client\Hooks\SiteLinksForDisplayLookup;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Client\Hooks\LangLinkHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class LangLinkHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepo = null;

	/**
	 * @var LangLinkHandler
	 */
	private $langLinkHandler;

	private function getItems() {
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
		$links->addNewSiteLink( 'srwiki', 'Talk:Foo sr' );
		$items[] = $item;

		return $items;
	}

	protected function setUp(): void {
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
			new NamespaceChecker( [ NS_TALK ] ),
			new SiteLinksForDisplayLookup(
				$this->mockRepo,
				$this->mockRepo,
				$this->createMock( UsageAccumulator::class ),
				$this->createMock( HookContainer::class ),
				new NullLogger(),
				'srwiki'
			),
			$siteStore,
			'srwiki',
			[ 'wikipedia' ]
		);
	}

	/**
	 * @return LanguageLinkBadgeDisplay
	 */
	private function getLanguageLinkBadgeDisplay() {
		$badgeDisplay = $this->createMock( LanguageLinkBadgeDisplay::class );

		$badgeDisplay->method( 'attachBadgesToOutput' )
			->willReturnCallback( function ( array $siteLinks, ParserOutput $parserOutput ) {
				$badges = $this->linksToBadges( $siteLinks );
				$parserOutput->setExtensionData( 'wikibase_badges', $badges );
			} );

		return $badgeDisplay;
	}

	/**
	 * @param SiteLink[] $siteLinks
	 *
	 * @return string[]
	 */
	public function linksToBadges( array $siteLinks ) {
		$badgesByPrefix = [];

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

	public function provideGetNoExternalLangLinks() {
		return [
			[ // #0
				[],
			],
			[ // #1
				[ '*' ],
			],
			[ // #2
				[ 'de' ],
			],
			[ // #3
				[ 'xy', 'de', 'en' ],
			],
		];
	}

	protected function makeParserOutput( array $langLinks, array $noExternalLangLinks = [] ) {
		$parserOutput = new ParserOutput();
		NoLangLinkHandler::appendNoExternalLangLinks( $parserOutput, $noExternalLangLinks );

		foreach ( $langLinks as $lang => $link ) {
			$parserOutput->addLanguageLink( "$lang:$link" );
		}

		return $parserOutput;
	}

	/**
	 * @dataProvider provideGetNoExternalLangLinks
	 */
	public function testGetNoExternalLangLinks( array $noExternalLangLinks ) {
		$parserOutput = $this->makeParserOutput( [], $noExternalLangLinks );
		$nel = $this->langLinkHandler->getNoExternalLangLinks( $parserOutput );

		$this->assertEquals( $noExternalLangLinks, $nel );
	}

	public function provideExcludeRepoLinks() {
		return [
			[ // #0
				[],
				[],
				[],
			],
			[ // #1
				[ 'de' ],
				[ 'cs' ],
				[ 'de', 'cs' ],
			],
			[
				[ 'de' ],
				[ '*' ],
				[ 'de', '*' ],
			],
			[ // #3
				[ 'xy', 'de', 'en' ],
				[],
				[ 'xy', 'de', 'en' ],
			],
		];
	}

	public function provideUseRepoLinks() {
		return [
			[ // #0
				'Foo_sr',
				[],
				true,
			],
			[ // #1
				'Foo_sr',
				[ '*' ],
				false,
			],
			[ // #2
				'Foo_sr',
				[ 'de' ],
				true,
			],
			[ // #3
				'Talk:Foo_sr',
				[],
				false,
			],
		];
	}

	/**
	 * @dataProvider provideUseRepoLinks
	 */
	public function testUseRepoLinks( $title, array $noExternalLangLinks, $expected ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromTextThrow( $title );
			$title->resetArticleID( 1 );
		}

		$parserOutput = $this->makeParserOutput( [], $noExternalLangLinks );

		$useRepoLinks = $this->langLinkHandler->useRepoLinks( $title, $parserOutput );

		$this->assertEquals( $expected, $useRepoLinks, "use repository links" );
	}

	public function provideGetEffectiveRepoLinks() {
		return [
			[ // #0: local overrides remote
				'Foo_sr', // title
				[ // langlinks
					'de' => 'Xoo de',
					'nl' => 'Foo nl',
				],
				[ // noexternallinks
				],
				[ // expectedLinks
					'enwiki' => 'Foo en',
				],
			],
			[ // #1: namespace not covered
				'Talk:Foo_sr', // title
				[ // langlinks
					'de' => 'Talk:Foo de',
					'nl' => 'Talk:Foo nl',
				],
				[ // noexternallinks
				],
				[ // expectedLinks
				],
			],
			[ // #2: disabled
				'Foo_sr', // title
				[ // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				],
				[ // noexternallinks
					'*',
				],
				[ // expectedLinks
				],
			],
			[ // #3: suppressed
				'Foo_sr', // title
				[ // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				],
				[ // noexternallinks
					'en',
				],
				[ // expectedLinks
				],
			],
			[ // #4: suppressed redundantly
				'Foo_sr', // title
				[ // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				],
				[ // noexternallinks
					'de',
				],
				[ // expectedLinks
					'enwiki' => 'Foo en',
				],
			],
		];
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
			$title = Title::newFromTextThrow( $title );
		}

		$parserOutput = $this->makeParserOutput( $langLinks, $noExternalLangLinks );

		$links = $this->langLinkHandler->getEffectiveRepoLinks( $title, $parserOutput );
		$links = $this->getPlainLinks( $links );

		$this->assertArrayEquals( $expectedLinks, $links, false, true );
	}

	/**
	 * @param SiteLink[] $links
	 *
	 * @return array
	 */
	private function getPlainLinks( array $links ) {
		$flat = [];

		foreach ( $links as $link ) {
			$key = $link->getSiteId();
			$flat[$key] = $link->getPageName();
		}

		return $flat;
	}

	public function provideAddLinksFromRepository() {
		$cases = $this->provideGetEffectiveRepoLinks();

		$badges = [
			// as defined by getItems()
			'Foo_en' => [
				'en' => [ 'Q17' ],
			],
			'Foo_sr' => [
				'en' => [ 'Q17' ],
			],
		];

		foreach ( $cases as $i => $case ) {
			// convert associative array to list of links
			$langLinks = $this->mapToLinks( $case[1] );
			$expectedLinks = $this->mapToLinks( $case[3] );

			// expect the expected effective links plus the provided language links
			$expectedLinks = array_merge( $expectedLinks, $langLinks );

			if ( !in_array( '*', $case[2] ) ) {
				$expectedBadges = $badges[ $case[0] ] ?? [];

				// no badges for languages mentioned in $noExternalLangLinks
				$expectedBadges = array_diff_key( $expectedBadges, array_flip( $case[2] ) );
			} else {
				$expectedBadges = [];
			}

			$cases[$i] = [
				$case[0],
				$case[1],
				$case[2],
				$expectedLinks,
				$expectedBadges,
			];
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
			$title = Title::newFromTextThrow( $title );
		}

		$parserOutput = $this->makeParserOutput( $langLinks, $noExternalLangLinks );

		$this->langLinkHandler->addLinksFromRepository( $title, $parserOutput );

		$this->assertArrayEquals( $expectedLinks, $parserOutput->getLanguageLinks(), false, false );
		$this->assertArrayEquals( $expectedBadges, $parserOutput->getExtensionData( 'wikibase_badges' ), false, true );
	}

	protected function mapToLinks( $map ) {
		$links = [];

		foreach ( $map as $wiki => $page ) {
			$lang = preg_replace( '/wiki$/', '', $wiki );
			$links[] = "$lang:$page";
		}

		return $links;
	}

	public function provideFilterRepoLinksByGroup() {
		return [
			[ // #0: nothing
				[], [], [],
			],
			[ // #1: nothing allowed
				[
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				],
				[],
				[],
			],
			[ // #2: nothing there
				[],
				[ 'wikipedia' ],
				[],
			],
			[ // #3: wikipedia only
				[
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				],
				[ 'wikipedia' ],
				[
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
				],
			],
		];
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
		return [
			[ // #0: nothing
				[], [], [],
			],
			[ // #1: nothing allowed
				[
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				],
				[ '*' ],
				[],
			],
			[ // #2: nothing there
				[],
				[ 'de' ],
				[],
			],
			[ // #3: no de
				[
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'enwiktionary' => 'Foo en word',
				],
				[ 'de' ],
				[
					'enwiki' => 'Foo en',
					//NOTE: srwiki is removed because that's a self-link
					'enwiktionary' => 'Foo en word',
				],
			],
		];
	}

	/**
	 * @dataProvider provideSuppressRepoLinks
	 */
	public function testSuppressRepoLinks( array $repoLinks, array $nel, array $expectedLinks ) {
		$parserOutput = new ParserOutput();
		foreach ( $nel as $lang ) {
			$parserOutput->appendExtensionData(
				NoLangLinkHandler::EXTENSION_DATA_KEY, $lang
			);
		}

		$actualLinks = $this->langLinkHandler->suppressRepoLinks( $parserOutput, $repoLinks );

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

		$simplewiki = new Site();
		$simplewiki->setGlobalId( 'simplewiki' );
		$simplewiki->setLanguageCode( 'en' );

		$fawiktionary = new Site();
		$fawiktionary->setGlobalId( 'fawiktionary' );
		$fawiktionary->setLanguageCode( 'fa' );

		$wikidatawiki = new Site();
		$wikidatawiki->setGlobalId( 'wikidatawiki' );
		$wikidatawiki->setLanguageCode( 'en' );

		return [
			[ $enwiki, 'en' ],
			[ $bexold, 'be-x-old' ],
			[ $dewikivoyage, 'de' ],
			[ $simplewiki, 'simple' ],
			[ $fawiktionary, 'fa' ],
			[ $wikidatawiki, 'en' ],
		];
	}

}
