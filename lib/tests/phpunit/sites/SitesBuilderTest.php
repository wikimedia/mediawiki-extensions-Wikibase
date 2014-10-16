<?php

use Wikibase\Test\MockSiteStore;

/**
 * @covers SitesBuilder
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SitesBuilderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider buildSitesProvider
	 */
	public function testBuildSites( $sites, $group, $wikiId, $expected ) {
		$store = new MockSiteStore();

		$validGroups = array( 'wikipedia', 'wikivoyage', 'wikiquote', 'wiktionary',
			'wikibooks', 'wikisource', 'wikiversity', 'wikinews' );

		$sitesBuilder = new SitesBuilder( $store, $validGroups );
		$sitesBuilder->buildStore( $sites, $group, $wikiId );

		$expectedSiteList = new SiteList( $expected );

		$this->assertEquals( $expectedSiteList, $store->getSites() );
	}

	public function buildSitesProvider() {
		$sitesData = $this->getSitesData();
		$sites = $this->getSites( $sitesData );
		$expectedSites = $sites;

		foreach( $expectedSites as $site ) {
			if ( $site->getGroup() === 'wikipedia' ) {
				$site->addInterwikiId( $site->getLanguageCode() );
				$site->addNavigationId( $site->getLanguageCode() );
			}
		}

		$data = array();

		$data[] = array( $sites, 'wikidata', null, $expectedSites );
		$data[] = array( $sites, 'commons', null, $expectedSites );
		$data[] = array( $sites, 'wikipedia', null, $expectedSites );
		$data[] = array( $sites, null, 'enwiki', $expectedSites );
		$data[] = array( $sites, null, 'commonswiki', $expectedSites );

		$expectedSites2 = $sites;

		foreach( $expectedSites2 as $site ) {
			if ( $site->getGroup() === 'wikivoyage' ) {
				$site->addInterwikiId( $site->getLanguageCode() );
				$site->addNavigationId( $site->getLanguageCode() );
			}
		}

		$data[] = array( $sites, 'wikivoyage', null, $expectedSites2 );
		$data[] = array( $sites, null, 'enwikivoyage', $expectedSites2 );
		$data[] = array( $sites, 'wikivoyage', 'enwiki', $expectedSites2 );

		$data[] = array( $sites, 'kittens', null, $sites );
		$data[] = array( $sites, 'kittens', 'enwiki', $sites );
		$data[] = array( $sites, null, 'kittenswiki', $sites );

		return $data;
	}

	protected function getSitesData() {
		$sitesData = array(
			array(
				'siteid' => 'enwiki',
				'group' => 'wikipedia',
				'url' => 'en.wikipedia.org',
				'lang' => 'en'
			),
			array(
				'siteid' => 'dewiki',
				'group' => 'wikipedia',
				'url' => 'de.wikipedia.org',
				'lang' => 'de'
			),
			array(
				'siteid' => 'enwikivoyage',
				'group' => 'wikivoyage',
				'url' => 'en.wikivoyage.org',
				'lang' => 'en'
			),
			array(
				'siteid' => 'frwikivoyage',
				'group' => 'wikivoyage',
				'url' => 'fr.wikivoyage.org',
				'lang' => 'fr'
			),
			array(
				'siteid' => 'enwikiquote',
				'group' => 'wikiquote',
				'url' => 'en.wikiquote.org',
				'lang' => 'en'
			),
			array(
				'siteid' => 'commonswiki',
				'group' => 'commons',
				'url' => 'commons.wikimedia.org',
				'lang' => 'en'
			),
			array(
				'siteid' => 'wikidatawiki',
				'group' => 'wikidata',
				'url' => 'www.wikidata.org',
				'lang' => 'en'
			),
		);

		return $sitesData;
	}

	/**
	 * @param array[] $sitesData
	 *
	 * @return MediaWikiSite[]
	 */
	protected function getSites( array $sitesData ) {
		$sites = array();

		foreach( $sitesData as $siteData ) {
			$fields = array(
				'globalid' => $siteData['siteid'],
				'type' => 'mediawiki',
				'group' => $siteData['group'],
				'source' => 'local',
				'language' => $siteData['lang'],
				'localids' => array(),
				'internalid' => null,
				'data' => array(
					'paths' => array(
						'file_path' => '//' . $siteData['url'] . '/w/$1',
						'page_path' => '//' . $siteData['url'] . '/wiki/$1'
					)
				),
				'forward' => false,
				'config' => array()
			);

			$site = new MediaWikiSite();
			$site->unserialize( serialize( $fields ) );
			$siteId = $siteData['siteid'];
			$sites[$siteId] = $site;
		}

		return $sites;
	}

}
