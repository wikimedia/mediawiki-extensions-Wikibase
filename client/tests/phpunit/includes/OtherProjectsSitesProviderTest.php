<?php

namespace Wikibase\Client\Test;

use MediaWikiSite;
use Site;
use SiteList;
use SiteStore;
use Wikibase\Client\OtherProjectsSitesProvider;
use Wikibase\Client\WikibaseClient;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Client\OtherProjectsSitesProvider
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 * @group WikibaseIntegration
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSitesProviderTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider otherProjectSitesProvider
	 */
	public function testOtherProjectSites( SiteStore $siteStore, array $supportedSites, Site $inputSite, SiteList $expectedSites ) {
		$otherProjectsSitesProvider = new OtherProjectsSitesProvider( $siteStore, $inputSite, array() );

		$this->assertEquals(
			$expectedSites,
			$otherProjectsSitesProvider->getOtherProjectsSites( $supportedSites )
		);
	}

	public function otherProjectSitesProvider() {
		$siteStore = new MockSiteStore( $this->getSites() );

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

	/**
	 * @return Site[]
	 */
	private function getSites() {
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
		$site->setGlobalId( 'nlwikisource' );
		$site->setGroup( 'wikisource' );
		$site->setLanguageCode( 'nl' );
		$sites[] = $site;

		$site = new MediaWikiSite();
		$site->setGlobalId( 'eswiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'es' );
		$sites[] = $site;

		$site = new MediaWikiSite();
		$site->setGlobalId( 'commonswiki' );
		$site->setGroup( 'commons' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new MediaWikiSite();
		$site->setGlobalId( 'wikidatawiki' );
		$site->setGroup( 'wikidata' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		return $sites;
	}

	/**
	 * Integration test... this is being used to generate the default settings.#
	 *
	 * @dataProvider getSiteIdsProvider
	 */
	public function testGetSiteIds( array $expected, $siteGlobalID, array $siteLinkGroups ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );
		$settings = $wikibaseClient->getSettings();
		$siteStore = $wikibaseClient->getSiteStore();

		$siteStore->clear();

		$sites = $this->getSites();

		$siteStore->saveSites( $sites );

		$oldSpecialSiteLinkGroups = $settings->getSetting( 'specialSiteLinkGroups' );
		$oldSiteGlobalId = $settings->getSetting( 'siteGlobalID' );
		$oldSiteLinkGroups = $settings->getSetting( 'siteLinkGroups' );

		$settings->setSetting( 'siteGlobalID', $siteGlobalID );
		$settings->setSetting( 'siteLinkGroups', $siteLinkGroups );
		$settings->setSetting( 'specialSiteLinkGroups', array( 'wikidata' ) );

		$siteIds = OtherProjectsSitesProvider::getSiteIds();

		$this->assertEquals(
			$expected,
			$siteIds
		);

		$settings->setSetting( 'specialSiteLinkGroups', $oldSpecialSiteLinkGroups );
		$settings->setSetting( 'siteLinkGroups', $oldSiteLinkGroups );
		$settings->setSetting( 'siteGlobalID', $oldSiteGlobalId );
		WikibaseClient::getDefaultInstance( 'reset' );
	}

	public function getSiteIdsProvider() {
		return array(
			'Only one commons' =>
				array( array( 'commonswiki' ), 'eswiki', array( 'wikipedia', 'commons' ) ),
			'Only one commons + same language' =>
				array( array( 'frwiki', 'commonswiki' ), 'frwikisource', array( 'wikipedia', 'wikisource', 'commons' ) ),
			'Same language' =>
				array( array( 'frwiki' ), 'frwikisource', array( 'wikipedia', 'wikisource' ) ),
			'No sister sites in language' =>
				array( array(), 'eswiki', array( 'wikipedia', 'wikisource', 'wikivoyage' ) ),
			'Only one siteLinkGroup' =>
				array( array(), 'enwiki', array( 'wikipedia' ) ),
			'Special siteLinkGroup' =>
				array( array( 'wikidatawiki' ), 'enwiki', array( 'wikipedia', 'special' ) )
		);
	}
}
