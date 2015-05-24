<?php

namespace Wikibase\Client\Tests\Hooks;

use Title;
use SiteStore;
use MediaWikiSite;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
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
	public function testBuildProjectLinkSidebar(
		array $siteIdsToOutput,
		array $result,
		SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay
	) {
		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkLookup(),
			$this->getSiteStore(),
			$siteIdsToOutput,
			$sidebarLinkBadgeDisplay
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
				array(),
				$this->getSidebarLinkBadgeDisplayWithoutBadge()
			),
			array(
				array( 'spam', 'spam2' ),
				array(),
				$this->getSidebarLinkBadgeDisplayWithoutBadge()
			),
			array(
				array( 'enwiktionary' ),
				array( $wiktionaryLink ),
				$this->getSidebarLinkBadgeDisplayWithoutBadge()
			),
			array(
				// Make sure results are sorted alphabetically by their group names
				array( 'enwiktionary', 'enwikiquote' ),
				array( $wikiquoteLink, $wiktionaryLink ),
				$this->getSidebarLinkBadgeDisplayWithoutBadge()
			),
			array(
				array( 'enwiki' ),
				array( $wikipediaLink ),
				$this->getSidebarLinkBadgeDisplayWithBadge()
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
	private function getSiteLinkLookup() {
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
				new SiteLink( 'enwiki', 'Nyan Cat', array( new ItemId( 'Q4242' ) ) ),
				new SiteLink( 'enwiktionary', 'Nyan Cat' )
			) ) );

		return $lookup;
	}

	private function getSidebarLinkBadgeDisplayWithBadge() {
		$sidebarLinkBadgeDisplay = $this->getMockBuilder( 'Wikibase\Client\Hooks\SidebarLinkBadgeDisplay' )
			->disableOriginalConstructor()
			->getMock();
		$sidebarLinkBadgeDisplay->expects( $this->any() )
			->method( 'getBadgeInfo' )
			->with( $this->equalTo( array( new ItemId( 'Q4242' ) ) ) )
			->will( $this->returnValue(  array( 'data' ) ) );
		$sidebarLinkBadgeDisplay->expects( $this->any() )
			->method( 'applyBadgeToLink' )
			->with(
				$this->equalTo( array(
					'msg' => 'wikibase-otherprojects-wikipedia',
					'class' => 'wb-otherproject-link wb-otherproject-wikipedia',
					'href' => 'https://en.wikipedia.org/wiki/Nyan_Cat',
					'hreflang' => 'en'
				) ),
				$this->equalTo( array( 'data' ) )
			);

		return $sidebarLinkBadgeDisplay;
	}

	private function getSidebarLinkBadgeDisplayWithoutBadge() {
		$sidebarLinkBadgeDisplay = $this->getMockBuilder( 'Wikibase\Client\Hooks\SidebarLinkBadgeDisplay' )
			->disableOriginalConstructor()
			->getMock();
		$sidebarLinkBadgeDisplay->expects( $this->any() )
			->method( 'getBadgeInfo' )
			->with( $this->equalTo( array() ) )
			->will( $this->returnValue(  array() ) );

		return $sidebarLinkBadgeDisplay;
	}
}
