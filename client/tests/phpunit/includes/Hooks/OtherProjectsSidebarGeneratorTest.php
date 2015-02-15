<?php

namespace Wikibase\Client\Tests\Hooks;

use Title;
use SiteStore;
use MediaWikiSite;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Client\Hooks\OtherProjectsSidebarGenerator
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
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
	 * @return SiteStore
	 */
	private function getSiteStore() {
		$siteStore = MockSiteStore::newFromTestSites();

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
	private function getSiteLinkLookup( ) {
		$Q123 = new ItemId( 'Q123' );

		$lookup = $this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' );
		$lookup->expects( $this->any() )
				->method( 'getEntityIdForSiteLink' )
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
