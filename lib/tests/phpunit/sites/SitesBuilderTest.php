<?php

use Wikibase\Test\MockSiteStore;

/**
 * @covers SitesBuilder
 *
 * @since 0.5
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SitesBuilderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider sitesProvider
	 */
	public function testBuildSites( $sites, $group, $wikiId, $expected ) {
		$store = new MockSiteStore();

		$sitesBuilder = new SitesBuilder( $store );
		$sitesBuilder->buildStore( $sites, $group, $wikiId );

		$expectedSiteList = new SiteList( $expected );

		$this->assertEquals( $expectedSiteList, $store->getSites() );
	}

	/**
	 * @dataProvider sitesProvider
	 */
	public function testAddInterwikiIdsToGroup( $sites, $group, $wikiId, $expected ) {
		$store = new MockSiteStore();

		$sitesBuilder = new SitesBuilder( $store );
		$sites = $sitesBuilder->addInterwikiIdsToGroup( $sites, $group, $wikiId );

		$this->assertEquals( $expected, $sites );
	}

	public function sitesProvider() {
		$sitesData = $this->getSitesData();
		$sites = $this->getSites( $sitesData );

		$groupData = array(
			'enwikivoyage' => 'en',
			'frwikivoyage' => 'fr'
		);

		$expectedSites = $sites;

		foreach( $expectedSites as $site ) {
			$siteId = $site->getGlobalId();

			if( array_key_exists( $siteId, $groupData ) ) {
				$site->addInterwikiId( $groupData[$siteId] );
				$site->addNavigationId( $groupData[$siteId] );
			}
		}

		return array(
			array( $sites, 'wikivoyage', null, $expectedSites ),
			array( $sites, 'wikidata', null, $sites ),
			array( $sites, null, 'enwikivoyage', $expectedSites )
		);
	}

	protected function getSitesData() {
		$sitesData = array(
			array(
				'id' => 'enwiki',
				'group' => 'wikipedia',
				'lang' => 'en'
			),
			array(
				'id' => 'dewiki',
				'group' => 'wikipedia',
				'lang' => 'de'
			),
			array(
				'id' => 'enwikivoyage',
				'group' => 'wikivoyage',
				'lang' => 'en'
			),
			array(
				'id' => 'frwikivoyage',
				'group' => 'wikivoyage',
				'lang' => 'fr'
			),
			array(
				'id' => 'enwikiquote',
				'group' => 'wikiquote',
				'lang' => 'en'
			),
			array(
				'id' => 'commonswiki',
				'group' => 'commons'
			),
			array(
				'id' => 'wikidatawiki',
				'group' => 'wikidata'
			)
		);

		return $sitesData;
	}

	protected function getSites( $sitesData ) {
		$sites = array();

		foreach( $sitesData as $siteData ) {
			$siteId = $siteData['id'];

			$site = new MediaWikiSite();
			$site->setGlobalId( $siteId );
			$site->setGroup( $siteData['group'] );

			if( array_key_exists( 'lang', $siteData ) ) {
				$site->setLanguageCode( $siteData['lang'] );
			}

			if( array_key_exists( 'interwiki', $siteData ) ) {
				$site->addInterwikiId( $siteData['interwiki'] );
				$site->addNavigationId( $siteData['interwiki'] );
			}

			$sites[$siteId] = $site;
		}

		return $sites;
	}
}
