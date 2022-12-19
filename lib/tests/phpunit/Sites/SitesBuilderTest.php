<?php

namespace Wikibase\Lib\Tests\Sites;

use HashSiteStore;
use MediaWikiSite;
use SiteList;
use Wikibase\Lib\Sites\SitesBuilder;

/**
 * @covers \Wikibase\Lib\Sites\SitesBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SitesBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider buildSitesProvider
	 */
	public function testBuildSites( array $sites, $group, $wikiId, array $expected ) {
		$store = new HashSiteStore();

		$validGroups = [ 'wikipedia', 'wikivoyage', 'wikiquote', 'wiktionary',
			'wikibooks', 'wikisource', 'wikiversity', 'wikinews' ];

		$sitesBuilder = new SitesBuilder( $store, $validGroups );
		$sitesBuilder->buildStore( $sites, $group, $wikiId );

		$expectedSiteList = new SiteList( $expected );

		$this->assertEquals( $expectedSiteList, $store->getSites() );
	}

	public function buildSitesProvider() {
		$sitesData = $this->getSitesData();
		$sites = $this->getSites( $sitesData );
		$expectedSites = $sites;

		foreach ( $expectedSites as $site ) {
			if ( $site->getGroup() === 'wikipedia' ) {
				$site->addInterwikiId( $site->getLanguageCode() );
				$site->addNavigationId( $site->getLanguageCode() );
			}
		}

		$data = [];

		$data[] = [ $sites, 'wikidata', null, $expectedSites ];
		$data[] = [ $sites, 'commons', null, $expectedSites ];
		$data[] = [ $sites, 'wikipedia', null, $expectedSites ];
		$data[] = [ $sites, null, 'enwiki', $expectedSites ];
		$data[] = [ $sites, null, 'commonswiki', $expectedSites ];

		$expectedSites2 = $sites;

		foreach ( $expectedSites2 as $site ) {
			if ( $site->getGroup() === 'wikivoyage' ) {
				$site->addInterwikiId( $site->getLanguageCode() );
				$site->addNavigationId( $site->getLanguageCode() );
			}
		}

		$data[] = [ $sites, 'wikivoyage', null, $expectedSites2 ];
		$data[] = [ $sites, null, 'enwikivoyage', $expectedSites2 ];
		$data[] = [ $sites, 'wikivoyage', 'enwiki', $expectedSites2 ];

		$data[] = [ $sites, 'kittens', null, $sites ];
		$data[] = [ $sites, 'kittens', 'enwiki', $sites ];
		$data[] = [ $sites, null, 'kittenswiki', $sites ];

		return $data;
	}

	private function getSitesData() {
		$sitesData = [
			[
				'siteid' => 'enwiki',
				'group' => 'wikipedia',
				'url' => 'en.wikipedia.org',
				'lang' => 'en',
			],
			[
				'siteid' => 'dewiki',
				'group' => 'wikipedia',
				'url' => 'de.wikipedia.org',
				'lang' => 'de',
			],
			[
				'siteid' => 'enwikivoyage',
				'group' => 'wikivoyage',
				'url' => 'en.wikivoyage.org',
				'lang' => 'en',
			],
			[
				'siteid' => 'frwikivoyage',
				'group' => 'wikivoyage',
				'url' => 'fr.wikivoyage.org',
				'lang' => 'fr',
			],
			[
				'siteid' => 'enwikiquote',
				'group' => 'wikiquote',
				'url' => 'en.wikiquote.org',
				'lang' => 'en',
			],
			[
				'siteid' => 'commonswiki',
				'group' => 'commons',
				'url' => 'commons.wikimedia.org',
				'lang' => 'en',
			],
			[
				'siteid' => 'wikidatawiki',
				'group' => 'wikidata',
				'url' => 'www.wikidata.org',
				'lang' => 'en',
			],
		];

		return $sitesData;
	}

	/**
	 * @param array[] $sitesData
	 *
	 * @return MediaWikiSite[]
	 */
	private function getSites( array $sitesData ) {
		$sites = [];

		foreach ( $sitesData as $siteData ) {
			$fields = [
				'globalid' => $siteData['siteid'],
				'type' => 'mediawiki',
				'group' => $siteData['group'],
				'source' => 'local',
				'language' => $siteData['lang'],
				'localids' => [],
				'internalid' => null,
				'data' => [
					'paths' => [
						'file_path' => '//' . $siteData['url'] . '/w/$1',
						'page_path' => '//' . $siteData['url'] . '/wiki/$1',
					],
				],
				'forward' => false,
				'config' => [],
			];

			$site = new MediaWikiSite();
			$site->__unserialize( $fields );
			$siteId = $siteData['siteid'];
			$sites[$siteId] = $site;
		}

		return $sites;
	}

}
