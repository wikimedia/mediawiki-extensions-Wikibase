<?php

namespace Wikibase\Client\Test;

use MediaWikiSite;
use Site;
use SiteList;
use SiteStore;
use Wikibase\Client\OtherProjectsSitesProvider;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Client\OtherProjectsSitesProvider
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class OtherProjectsSitesProviderTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider otherProjectSitesProvider
	 */
	public function testOtherProjectSites( SiteStore $siteStore, array $supportedSites, Site $inputSite, SiteList $expectedSites ) {
		$otherProjectsSitesProvider = new OtherProjectsSitesProvider( $siteStore, $inputSite );

		$this->assertEquals(
			$expectedSites,
			$otherProjectsSitesProvider->getOtherProjectsSites( $supportedSites )
		);
	}

	public function otherProjectSitesProvider() {
		$siteStore = $this->getSiteStore();

		$tests = array();

		$result = new SiteList();
		$result[] = $siteStore->getSite( 'frwiki' );
		$tests[] = array(
			$siteStore,
			array( 'wikipedia', 'wikisource' ),
			$siteStore->getSite( 'frwikisource' ),
			$result
		);

		$result = new SiteList();
		$result[] = $siteStore->getSite( 'frwiki' );
		$result[] = $siteStore->getSite( 'commonswiki' );
		$tests[] = array(
			$siteStore,
			array( 'wikipedia', 'wikisource', 'commons' ),
			$siteStore->getSite( 'frwikisource' ),
			$result
		);

		return $tests;
	}

	private function getSiteStore() {
		$sites = array();

		$site = new Site();
		$site->setGlobalId( 'foo' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'bar' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new MediaWikiSite();
		$site->setGlobalId( 'enwiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new MediaWikiSite();
		$site->setGlobalId( 'frwiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new MediaWikiSite();
		$site->setGlobalId( 'frwikisource' );
		$site->setGroup( 'wikisource' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new MediaWikiSite();
		$site->setGlobalId( 'commonswiki' );
		$site->setGroup( 'commons' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		return new MockSiteStore( $sites );
	}
}
