<?php

namespace Wikibase\Client\Tests\Hooks;

use Closure;
use HashSiteStore;
use MediaWikiSite;
use SiteStore;
use Title;
use TestSites;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Client\Hooks\OtherProjectsSidebarGenerator
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSidebarGeneratorTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider projectLinkSidebarProvider
	 */
	public function testBuildProjectLinkSidebar( array $siteIdsToOutput, array $result ) {
		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkLookup(),
			$this->getSiteStore(),
			$siteIdsToOutput
		);

		$this->assertEquals(
			$result,
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( Title::makeTitle( NS_MAIN, 'Nyan Cat' ) )
		);
	}

	public function projectLinkSidebarProvider() {
		$wiktionaryLink = array(
			'msg' => 'wikibase-otherprojects-wiktionary',
			'class' => 'wb-otherproject-link wb-otherproject-wiktionary',
			'href' => 'https://en.wiktionary.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		);
		$wikiquoteLink = array(
			'msg' => 'wikibase-otherprojects-wikiquote',
			'class' => 'wb-otherproject-link wb-otherproject-wikiquote',
			'href' => 'https://en.wikiquote.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		);
		$wikipediaLink = array(
			'msg' => 'wikibase-otherprojects-wikipedia',
			'class' => 'wb-otherproject-link wb-otherproject-wikipedia',
			'href' => 'https://en.wikipedia.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		);

		return array(
			array(
				array(),
				array()
			),
			array(
				array( 'spam', 'spam2' ),
				array()
			),
			array(
				array( 'enwiktionary' ),
				array( $wiktionaryLink )
			),
			array(
				// Make sure results are sorted alphabetically by their group names
				array( 'enwiktionary', 'enwiki', 'enwikiquote' ),
				array( $wikipediaLink, $wikiquoteLink, $wiktionaryLink )
			)
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
		$this->mergeMwGlobalArrayValue( 'wgHooks', array(
			'WikibaseClientOtherProjectsSidebar' => array( $handler ),
		) );

		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkLookup(),
			$this->getSiteStore(),
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
		$wiktionaryLink = array(
			'msg' => 'wikibase-otherprojects-wiktionary',
			'class' => 'wb-otherproject-link wb-otherproject-wiktionary',
			'href' => 'https://en.wiktionary.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		);
		$wikiquoteLink = array(
			'msg' => 'wikibase-otherprojects-wikiquote',
			'class' => 'wb-otherproject-link wb-otherproject-wikiquote',
			'href' => 'https://en.wikiquote.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		);
		$wikipediaLink = array(
			'msg' => 'wikibase-otherprojects-wikipedia',
			'class' => 'wb-otherproject-link wb-otherproject-wikipedia',
			'href' => 'https://en.wikipedia.org/wiki/Nyan_Cat',
			'hreflang' => 'en'
		);
		$changedWikipedaLink = array(
			'msg' => 'wikibase-otherprojects-wikipedia',
			'class' => 'wb-otherproject-link wb-otherproject-wikipedia',
			'href' => 'https://en.wikipedia.org/wiki/Cat',
			'hreflang' => 'en'
		);

		return array(
			'Noop hook, gets the right data' => array(
				function( ItemId $itemId, array &$sidebar ) use ( $wikipediaLink, $wikiquoteLink, $wiktionaryLink ) {
					$this->assertSame(
						array(
							'wikiquote' => array( 'enwikiquote' => $wikiquoteLink ),
							'wikipedia' => array( 'enwiki' => $wikipediaLink ),
							'wiktionary' => array( 'enwiktionary' => $wiktionaryLink )
						),
						$sidebar
					);
					$this->assertSame( 'Q123', $itemId->getSerialization() );
				},
				array( 'enwiktionary', 'enwiki', 'enwikiquote' ),
				array( $wikipediaLink, $wikiquoteLink, $wiktionaryLink )
			),
			'Hook changes enwiki link' => array(
				function( ItemId $itemId, array &$sidebar ) use ( $changedWikipedaLink ) {
					$sidebar['wikipedia']['enwiki']['href'] = $changedWikipedaLink['href'];
				},
				array( 'enwiktionary', 'enwiki', 'enwikiquote' ),
				array( $changedWikipedaLink, $wikiquoteLink, $wiktionaryLink )
			),
			'Hook inserts enwiki link' => array(
				function( ItemId $itemId, array &$sidebar ) use ( $changedWikipedaLink ) {
					$this->assertFalse(
						isset( $sidebar['wikipedia'] ),
						'No Wikipedia link present yet'
					);

					$sidebar['wikipedia']['enwiki'] = $changedWikipedaLink;
				},
				array( 'enwiktionary', 'enwikiquote' ),
				array( $changedWikipedaLink, $wikiquoteLink, $wiktionaryLink )
			),
			'Invalid hook #1, original data is being used' => array(
				function( ItemId $itemId, array &$sidebar ) {
					$sidebar = null;
				},
				array( 'enwiktionary', 'enwiki', 'enwikiquote' ),
				array( $wikipediaLink, $wikiquoteLink, $wiktionaryLink ),
				true
			),
			'Invalid hook #2, original data is being used' => array(
				function( ItemId $itemId, array &$sidebar ) {
					$sidebar[0]['msg'] = array();
				},
				array( 'enwiktionary', 'enwiki', 'enwikiquote' ),
				array( $wikipediaLink, $wikiquoteLink, $wiktionaryLink ),
				true
			),
			'Invalid hook #3, original data is being used' => array(
				function( ItemId $itemId, array &$sidebar ) use ( $changedWikipedaLink ) {
					$sidebar['wikipedia']['enwiki']['href'] = 1.2;
				},
				array( 'enwiktionary', 'enwiki', 'enwikiquote' ),
				array( $wikipediaLink, $wikiquoteLink, $wiktionaryLink ),
				true
			),
			'Invalid hook #4, original data is being used' => array(
				function( ItemId $itemId, array &$sidebar ) use ( $changedWikipedaLink ) {
					$sidebar['wikipedia'][] = $changedWikipedaLink;
				},
				array( 'enwiktionary', 'enwiki', 'enwikiquote' ),
				array( $wikipediaLink, $wikiquoteLink, $wiktionaryLink ),
				true
			),
		);
	}

	public function testBuildProjectLinkSidebar_hookNotCalledIfPageNotConnected() {
		$this->mergeMwGlobalArrayValue( 'wgHooks', array(
			'WikibaseClientOtherProjectsSidebar' => array(
				function () {
					$this->fail( 'Should not get called.' );
				},
			),
		) );

		$lookup = $this->getMock( SiteLinkLookup::class );
		$lookup->expects( $this->any() )
				->method( 'getItemIdForSiteLink' )
				->will( $this->returnValue( null ) );

		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$lookup,
			$this->getSiteStore(),
			array( 'enwiki' )
		);

		$this->assertSame(
			array(),
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( Title::makeTitle( NS_MAIN, 'Nyan Cat' ) )
		);
	}

	public function testBuildProjectLinkSidebar_hookCalledWithEmptySidebar() {
		$called = false;

		$this->mergeMwGlobalArrayValue( 'wgHooks', array(
			'WikibaseClientOtherProjectsSidebar' => array(
				function ( ItemId $itemId, $sidebar ) use ( &$called ) {
					$this->assertSame( 'Q123', $itemId->getSerialization() );
					$this->assertSame( array(), $sidebar );
					$called = true;
				},
			),
		) );

		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkLookup(),
			$this->getSiteStore(),
			array( 'unknown-site' )
		);

		$this->assertSame(
			array(),
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( Title::makeTitle( NS_MAIN, 'Nyan Cat' ) )
		);
		$this->assertTrue( $called, 'Hook needs to be called' );
	}

	/**
	 * @return SiteStore
	 */
	private function getSiteStore() {
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
		$Q123 = new ItemId( 'Q123' );

		$lookup = $this->getMock( SiteLinkLookup::class );
		$lookup->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnValue( $Q123 ) );

		$lookup->expects( $this->any() )
			->method( 'getSiteLinksForItem' )
			->with( $Q123 )
			->will( $this->returnValue( array(
				new SiteLink( 'enwikiquote', 'Nyan Cat' ),
				new SiteLink( 'enwiki', 'Nyan Cat' ),
				new SiteLink( 'enwiktionary', 'Nyan Cat' )
			) ) );

		return $lookup;
	}

}
