<?php

namespace Wikibase\Lib\Tests\Sites;

use MediaWikiSite;
use Wikibase\Lib\Sites\SiteMatrixParser;

/**
 * @covers \Wikibase\Lib\Sites\SiteMatrixParser
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteMatrixParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider sitesFromJsonProvider
	 */
	public function testSitesFromJson( $scriptPath, $articlePath, $protocol, $expected ) {
		$json = $this->getSiteMatrixJson();

		$siteMatrixParser = new SiteMatrixParser(
			$scriptPath,
			$articlePath,
			$protocol
		);

		$sites = $siteMatrixParser->sitesFromJson( $json );

		ksort( $expected );
		ksort( $sites );

		$this->assertEquals( $expected, $sites );
	}

	public function sitesFromJsonProvider() {
		$siteData = $this->getSiteData();

		$data = [];

		$data['Protocol relative'] = [
			'/w/$1',
			'/wiki/$1',
			false,
			$this->getSites( $siteData, '/w/$1', '/wiki/$1' ),
		];

		$data['Keep the protocol'] = [
			'/w/$1',
			'/wiki/$1',
			true,
			$this->getSites( $siteData, '/w/$1', '/wiki/$1', 'http:' ),
		];

		$data['Force a protocol'] = [
			'/w/$1',
			'/wiki/$1',
			'CompuGlobalHyperMegaNet',
			$this->getSites( $siteData, '/w/$1', '/wiki/$1', 'CompuGlobalHyperMegaNet:' ),
		];

		return $data;
	}

	protected function getSiteMatrixJson() {
		$sites = [
			[
				'code' => 'en',
				'name' => 'English',
				'site' => [
					[
						'url' => 'http://en.wikipedia.org',
						'dbname' => 'enwiki',
						'code' => 'wiki',
						'sitename' => 'Wikipedia',
					],
					[
						'url' => 'http://en.wikivoyage.org',
						'dbname' => 'enwikivoyage',
						'code' => 'wikivoyage',
						'sitename' => 'Wikipedia',
					],
					[
						'url' => 'http://en.wikiquote.org',
						'dbname' => 'enwikiquote',
						'code' => 'wikiquote',
						'sitename' => 'Wikipedia',
					],
				],
			],
			[
				'code' => 'fr',
				'name' => 'français',
				'site' => [
					[
						'url' => 'http://fr.wikipedia.org',
						'dbname' => 'frwiki',
						'code' => 'wiki',
						'sitename' => 'Wikipedia',
					],
					[
						'url' => 'http://fr.wikivoyage.org',
						'dbname' => 'frwikivoyage',
						'code' => 'wikivoyage',
						'sitename' => 'Wikipedia',
					],
				],
			],
		];

		$specialSites = [];

		$specialSites[] = [
			'url' => 'http://commons.wikimedia.org',
			'dbname' => 'commonswiki',
			'code' => 'commons',
		];

		$specialSites[] = [
			'url' => 'http://www.wikidata.org',
			'dbname' => 'wikidatawiki',
			'code' => 'wikidata',
		];

		$specials = [
			'specials' => $specialSites,
		];

		$siteMatrix = array_merge(
			[ 'count' => 879 ],
			$sites,
			$specials
		);

		$data = [
			'sitematrix' => $siteMatrix,
		];

		return json_encode( $data );
	}

	protected function getSiteData() {
		$siteData = [
			[
				'siteid' => 'enwiki',
				'group' => 'wikipedia',
				'url' => 'en.wikipedia.org',
				'lang' => 'en',
				'localids' => [],
			],
			[
				'siteid' => 'frwiki',
				'group' => 'wikipedia',
				'url' => 'fr.wikipedia.org',
				'lang' => 'fr',
				'localids' => [],
			],
			[
				'siteid' => 'enwikivoyage',
				'group' => 'wikivoyage',
				'url' => 'en.wikivoyage.org',
				'lang' => 'en',
				'localids' => [],
			],
			[
				'siteid' => 'frwikivoyage',
				'group' => 'wikivoyage',
				'url' => 'fr.wikivoyage.org',
				'lang' => 'fr',
				'localids' => [],
			],
			[
				'siteid' => 'enwikiquote',
				'group' => 'wikiquote',
				'url' => 'en.wikiquote.org',
				'lang' => 'en',
				'localids' => [],
			],
			[
				'siteid' => 'commonswiki',
				'group' => 'commons',
				'url' => 'commons.wikimedia.org',
				'lang' => 'en',
				'localids' => [ 'interwiki' => [ 'commons' ] ],
			],
			[
				'siteid' => 'wikidatawiki',
				'group' => 'wikidata',
				'url' => 'www.wikidata.org',
				'lang' => 'en',
				'localids' => [ 'interwiki' => [ 'wikidata' ] ],
			],
		];

		return $siteData;
	}

	public function getSites( array $sitesData, $scriptPath, $articlePath, $protocol = '' ) {
		$sites = [];

		foreach ( $sitesData as $siteData ) {
			$fields = [
				'globalid' => $siteData['siteid'],
				'type' => 'mediawiki',
				'group' => $siteData['group'],
				'source' => 'local',
				'language' => $siteData['lang'],
				'localids' => $siteData['localids'],
				'internalid' => null,
				'data' => [
					'paths' => [
						'file_path' => $protocol . '//' . $siteData['url'] . $scriptPath,
						'page_path' => $protocol . '//' . $siteData['url'] . $articlePath,
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
