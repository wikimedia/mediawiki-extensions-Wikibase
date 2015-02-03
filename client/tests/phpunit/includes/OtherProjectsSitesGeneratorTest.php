<?php

namespace Wikibase\Client\Tests;

use MediaWikiSite;
use Site;
use Wikibase\Client\OtherProjectsSitesGenerator;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Client\OtherProjectsSitesGenerator
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
class OtherProjectsSitesGeneratorTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider otherProjectSitesProvider
	 */
	public function testOtherProjectSiteIds( array $supportedSites, $localSiteId, $expectedSiteIds ) {
		$siteStore = $this->getSiteStoreMock();
		$otherProjectsSitesProvider = new OtherProjectsSitesGenerator( $siteStore, $localSiteId, array( 'wikidata' ) );

		$this->assertEquals(
			$expectedSiteIds,
			$otherProjectsSitesProvider->getOtherProjectsSiteIds( $supportedSites )
		);
	}

	public function otherProjectSitesProvider() {
		$tests = array();

		$tests['Same language'] = array(
			array( 'wikipedia', 'wikisource' ),
			'frwikisource',
			array( 'frwiki' )
		);

		$tests['Same language + only one in group'] = array(
			array( 'wikipedia', 'wikisource', 'commons' ),
			'frwikisource',
			array( 'frwiki', 'commonswiki' )
		);

		$tests['Only one in group'] = array(
			array( 'wikipedia', 'wikisource', 'commons' ),
			'eswiki',
			array( 'commonswiki' )
		);

		$tests['Special group'] = array(
			array( 'wikipedia', 'wikisource', 'special' ),
			'eswiki',
			array( 'wikidatawiki' )
		);

		$tests['Special group + language'] = array(
			array( 'wikipedia', 'wikisource', 'special' ),
			'frwiki',
			array( 'frwikisource', 'wikidatawiki' )
		);

		$tests['No other sites'] = array(
			array( 'wikipedia', 'wikisource' ),
			'eswiki',
			array()
		);

		return $tests;
	}

	/**
	 * @return MockSiteStore
	 */
	private function getSiteStoreMock() {
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

		return new MockSiteStore( $sites );
	}

}
