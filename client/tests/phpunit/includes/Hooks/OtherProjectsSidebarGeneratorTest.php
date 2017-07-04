<?php

namespace Wikibase\Client\Tests\Hooks;

use Closure;
use HashSiteStore;
use Language;
use MediaWikiSite;
use SiteLookup;
use Title;
use TestSites;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Client\Hooks\OtherProjectsSidebarGenerator
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSidebarGeneratorTest extends \MediaWikiTestCase {

	const TEST_ITEM_ID = 'Q123';
	const BADGE_ITEM_ID = 'Q4242';
	const BADGE_ITEM_LABEL = 'Badge Label';
	const BADGE_CSS_CLASS = 'badge-class';

	/**
	 * @dataProvider projectLinkSidebarProvider
	 */
	public function testBuildProjectLinkSidebar(
		array $siteIdsToOutput,
		array $result,
		SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay
	) {

		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkLookup(),
			$this->getSiteLookup(),
			$this->getEntityLookup(),
			$sidebarLinkBadgeDisplay,
			$siteIdsToOutput
		);

		$this->assertEquals(
			$result,
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( Title::makeTitle( NS_MAIN, 'Nyan Cat' ) )
		);
	}

	public function projectLinkSidebarProvider() {
		$wiktionaryLink = [
			'msg' => 'wikibase-otherprojects-wiktionary',
			'class' => 'wb-otherproject-link wb-otherproject-wiktionary',
			'href' => 'https://en.wiktionary.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		];
		$wikiquoteLink = [
			'msg' => 'wikibase-otherprojects-wikiquote',
			'class' => 'wb-otherproject-link wb-otherproject-wikiquote',
			'href' => 'https://en.wikiquote.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		];
		$wikipediaLink = [
			'msg' => 'wikibase-otherprojects-wikipedia',
			'class' => 'wb-otherproject-link wb-otherproject-wikipedia ' .
				'badge-' . self::BADGE_ITEM_ID . ' ' . self::BADGE_CSS_CLASS,
			'href' => 'https://en.wikipedia.org/wiki/Nyan_Cat',
			'hreflang' => 'en',
			'itemtitle' => self::BADGE_ITEM_LABEL,
		];

		return [
			[
				[],
				[],
				$this->getSidebarLinkBadgeDisplay()
			],
			[
				[ 'spam', 'spam2' ],
				[],
				$this->getSidebarLinkBadgeDisplay()
			],
			[
				[ 'enwiktionary' ],
				[ $wiktionaryLink ],
				$this->getSidebarLinkBadgeDisplay()
			],
			[
				[ 'enwiki' ],
				[ $wikipediaLink ],
				$this->getSidebarLinkBadgeDisplay()
			],
			[
				// Make sure results are sorted alphabetically by their group names
				[ 'enwiktionary', 'enwiki', 'enwikiquote' ],
				[ $wikipediaLink, $wikiquoteLink, $wiktionaryLink ],
				$this->getSidebarLinkBadgeDisplay()
			],
		];
	}

	/**
	 * @dataProvider projectLinkSidebarProvider
	 */
	public function testBuildProjectLinkSidebarFromItemId( array $siteIdsToOutput, array $result ) {
		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkLookup(),
			$this->getSiteLookup(),
			$this->getEntityLookup(),
			$this->getSidebarLinkBadgeDisplay(),
			$siteIdsToOutput
		);

		$this->assertEquals(
			$result,
			$otherProjectSidebarGenerator->buildProjectLinkSidebarFromItemId( new ItemId( self::TEST_ITEM_ID ) )
		);
	}

	/**
	 * @dataProvider projectLinkSidebarHookProvider
	 */
	public function testBuildProjectLinkSidebar_hook(
		Closure $handler,
		array $siteIdsToOutput,
		array $result,
		$suppressErrors = false
	) {
		$this->mergeMwGlobalArrayValue( 'wgHooks', [
			'WikibaseClientOtherProjectsSidebar' => [ $handler ],
		] );

		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkLookup(),
			$this->getSiteLookup(),
			$this->getEntityLookup(),
			$this->getSidebarLinkBadgeDisplay(),
			$siteIdsToOutput
		);

		if ( $suppressErrors ) {
			\MediaWiki\suppressWarnings();
		}
		$this->assertEquals(
			$result,
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( Title::makeTitle( NS_MAIN, 'Nyan Cat' ) )
		);

		if ( $suppressErrors ) {
			\MediaWiki\restoreWarnings();
		}
	}

	public function projectLinkSidebarHookProvider() {
		$wiktionaryLink = [
			'msg' => 'wikibase-otherprojects-wiktionary',
			'class' => 'wb-otherproject-link wb-otherproject-wiktionary',
			'href' => 'https://en.wiktionary.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		];
		$wikiquoteLink = [
			'msg' => 'wikibase-otherprojects-wikiquote',
			'class' => 'wb-otherproject-link wb-otherproject-wikiquote',
			'href' => 'https://en.wikiquote.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		];
		$wikipediaLink = [
			'msg' => 'wikibase-otherprojects-wikipedia',
			'class' => 'wb-otherproject-link wb-otherproject-wikipedia ' .
				'badge-' . self::BADGE_ITEM_ID . ' ' . self::BADGE_CSS_CLASS,
			'href' => 'https://en.wikipedia.org/wiki/Nyan_Cat',
			'hreflang' => 'en',
			'itemtitle' => self::BADGE_ITEM_LABEL,
		];
		$changedWikipedaLink = [
			'msg' => 'wikibase-otherprojects-wikipedia',
			'class' => 'wb-otherproject-link wb-otherproject-wikipedia ' .
				'badge-' . self::BADGE_ITEM_ID . ' ' . self::BADGE_CSS_CLASS,
			'href' => 'https://en.wikipedia.org/wiki/Cat',
			'hreflang' => 'en',
			'itemtitle' => self::BADGE_ITEM_LABEL,
		];

		return [
			'Noop hook, gets the right data' => [
				function( ItemId $itemId, array &$sidebar ) use ( $wikipediaLink, $wikiquoteLink, $wiktionaryLink ) {
					$this->assertSame(
						[
							'wikiquote' => [ 'enwikiquote' => $wikiquoteLink ],
							'wikipedia' => [ 'enwiki' => $wikipediaLink ],
							'wiktionary' => [ 'enwiktionary' => $wiktionaryLink ]
						],
						$sidebar
					);
					$this->assertSame( self::TEST_ITEM_ID, $itemId->getSerialization() );
				},
				[ 'enwiktionary', 'enwiki', 'enwikiquote' ],
				[ $wikipediaLink, $wikiquoteLink, $wiktionaryLink ]
			],
			'Hook changes enwiki link' => [
				function( ItemId $itemId, array &$sidebar ) use ( $changedWikipedaLink ) {
					$sidebar['wikipedia']['enwiki']['href'] = $changedWikipedaLink['href'];
				},
				[ 'enwiktionary', 'enwiki', 'enwikiquote' ],
				[ $changedWikipedaLink, $wikiquoteLink, $wiktionaryLink ]
			],
			'Hook inserts enwiki link' => [
				function( ItemId $itemId, array &$sidebar ) use ( $changedWikipedaLink ) {
					$this->assertArrayNotHasKey(
						'wikipedia',
						$sidebar,
						'No Wikipedia link present yet'
					);

					$sidebar['wikipedia']['enwiki'] = $changedWikipedaLink;
				},
				[ 'enwiktionary', 'enwikiquote' ],
				[ $changedWikipedaLink, $wikiquoteLink, $wiktionaryLink ]
			],
			'Invalid hook #1, original data is being used' => [
				function( ItemId $itemId, array &$sidebar ) {
					$sidebar = null;
				},
				[ 'enwiktionary', 'enwiki', 'enwikiquote' ],
				[ $wikipediaLink, $wikiquoteLink, $wiktionaryLink ],
				true
			],
			'Invalid hook #2, original data is being used' => [
				function( ItemId $itemId, array &$sidebar ) {
					$sidebar[0]['msg'] = [];
				},
				[ 'enwiktionary', 'enwiki', 'enwikiquote' ],
				[ $wikipediaLink, $wikiquoteLink, $wiktionaryLink ],
				true
			],
			'Invalid hook #3, original data is being used' => [
				function( ItemId $itemId, array &$sidebar ) use ( $changedWikipedaLink ) {
					$sidebar['wikipedia']['enwiki']['href'] = 1.2;
				},
				[ 'enwiktionary', 'enwiki', 'enwikiquote' ],
				[ $wikipediaLink, $wikiquoteLink, $wiktionaryLink ],
				true
			],
			'Invalid hook #4, original data is being used' => [
				function( ItemId $itemId, array &$sidebar ) use ( $changedWikipedaLink ) {
					$sidebar['wikipedia'][] = $changedWikipedaLink;
				},
				[ 'enwiktionary', 'enwiki', 'enwikiquote' ],
				[ $wikipediaLink, $wikiquoteLink, $wiktionaryLink ],
				true
			],
		];
	}

	public function testBuildProjectLinkSidebar_hookNotCalledIfPageNotConnected() {
		$this->mergeMwGlobalArrayValue( 'wgHooks', [
			'WikibaseClientOtherProjectsSidebar' => [
				function () {
					$this->fail( 'Should not get called.' );
				},
			],
		] );

		$lookup = $this->getMock( SiteLinkLookup::class );
		$lookup->expects( $this->any() )
				->method( 'getItemIdForSiteLink' )
				->will( $this->returnValue( null ) );

		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$lookup,
			$this->getSiteLookup(),
			$this->getEntityLookup(),
			$this->getSidebarLinkBadgeDisplay(),
			[ 'enwiki' ]
		);

		$this->assertSame(
			[],
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( Title::makeTitle( NS_MAIN, 'Nyan Cat' ) )
		);
	}

	public function testBuildProjectLinkSidebar_hookCalledWithEmptySidebar() {
		$called = false;

		$this->mergeMwGlobalArrayValue( 'wgHooks', [
			'WikibaseClientOtherProjectsSidebar' => [
				function ( ItemId $itemId, $sidebar ) use ( &$called ) {
					$this->assertSame( self::TEST_ITEM_ID, $itemId->getSerialization() );
					$this->assertSame( [], $sidebar );
					$called = true;
				},
			],
		] );

		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkLookup(),
			$this->getSiteLookup(),
			$this->getEntityLookup(),
			$this->getSidebarLinkBadgeDisplay(),
			[ 'unknown-site' ]
		);

		$this->assertSame(
			[],
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( Title::makeTitle( NS_MAIN, 'Nyan Cat' ) )
		);
		$this->assertTrue( $called, 'Hook needs to be called' );
	}

	/**
	 * @return SiteLookup
	 */
	private function getSiteLookup() {
		$siteStore = new HashSiteStore( TestSites::getSites() );

		$site = new MediaWikiSite();
		$site->setGlobalId( 'enwikiquote' );
		$site->setGroup( 'wikiquote' );
		$site->setLanguageCode( 'en' );
		$site->setPath( MediaWikiSite::PATH_PAGE, "https://en.wikiquote.org/wiki/$1" );
		$siteStore->saveSite( $site );

		return $siteStore;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getSiteLinkLookup() {
		$itemId = new ItemId( self::TEST_ITEM_ID );

		$lookup = $this->getMock( SiteLinkLookup::class );
		$lookup->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnValue( $itemId ) );

		$lookup->expects( $this->any() )
			->method( 'getSiteLinksForItem' )
			->with( $itemId )
			->will( $this->returnValue( [
				new SiteLink( 'enwikiquote', 'Nyan Cat' ),
				new SiteLink( 'enwiki', 'Nyan Cat' ),
				new SiteLink( 'enwiktionary', 'Nyan Cat' )
			] ) );

		return $lookup;
	}

	private function getEntityLookup() {
		$item = new Item( new ItemId( self::TEST_ITEM_ID ) );
		$item->setSiteLinkList( new SiteLinkList( [
			new SiteLink( 'enwikiquote', 'Nyan Cat' ),
			new SiteLink( 'enwiki', 'Nyan Cat', [ new ItemId( self::BADGE_ITEM_ID ) ] ),
			new SiteLink( 'enwiktionary', 'Nyan Cat' ),
		] ) );

		$lookup = new InMemoryEntityLookup();
		$lookup->addEntity( $item );

		return $lookup;
	}

	/**
	 * @return SidebarLinkBadgeDisplay
	 */
	private function getSidebarLinkBadgeDisplay() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->method( 'getLabel' )
			->with( new ItemId( self::BADGE_ITEM_ID ) )
			->will( $this->returnValue( new Term( 'en', self::BADGE_ITEM_LABEL ) ) );

		return new SidebarLinkBadgeDisplay(
			$labelDescriptionLookup,
			[ self::BADGE_ITEM_ID => self::BADGE_CSS_CLASS ],
			new Language( 'en' )
		);
	}

}
