<?php

namespace Wikibase\Test;

use MediaWikiSite;
use PHPUnit_Framework_TestCase;
use Wikibase\Lib\Sites\SiteMatrixParser;

/**
 * @covers Wikibase\Lib\Sites\SiteMatrixParser
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteMatrixParserTest extends PHPUnit_Framework_TestCase {

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

		$data['Protocol relative'] = array(
			'/w/$1',
			'/wiki/$1',
			false,
			$this->getSites( $siteData, '/w/$1', '/wiki/$1' )
		);

		$data['Keep the protocol'] = array(
			'/w/$1',
			'/wiki/$1',
			true,
			$this->getSites( $siteData, '/w/$1', '/wiki/$1', 'http:' )
		);

		$data['Force a protocol'] = array(
			'/w/$1',
			'/wiki/$1',
			'CompuGlobalHyperMegaNet',
			$this->getSites( $siteData, '/w/$1', '/wiki/$1', 'CompuGlobalHyperMegaNet:' )
		);

		return $data;
	}

	protected function getSiteMatrixJson() {
		$sites = array(
			array(
				'code' => 'en',
				'name' => 'English',
				'site' => array(
					array(
						'url' => 'http://en.wikipedia.org',
						'dbname' => 'enwiki',
						'code' => 'wiki',
						'sitename' => 'Wikipedia'
					),
					array(
						'url' => 'http://en.wikivoyage.org',
						'dbname' => 'enwikivoyage',
						'code' => 'wikivoyage',
						'sitename' => 'Wikipedia'
					),
					array(
						'url' => 'http://en.wikiquote.org',
						'dbname' => 'enwikiquote',
						'code' => 'wikiquote',
						'sitename' => 'Wikipedia'
					)
				),
			),
			array(
				'code' => 'fr',
				'name' => 'français',
				'site' => array(
					array(
						'url' => 'http://fr.wikipedia.org',
						'dbname' => 'frwiki',
						'code' => 'wiki',
						'sitename' => 'Wikipedia'
					),
					array(
						'url' => 'http://fr.wikivoyage.org',
						'dbname' => 'frwikivoyage',
						'code' => 'wikivoyage',
						'sitename' => 'Wikipedia'
					)
				)
			)
		);

		$specialSites = [];

		$specialSites[] = array(
			'url' => 'http://commons.wikimedia.org',
			'dbname' => 'commonswiki',
			'code' => 'commons'
		);

		$specialSites[] = array(
			'url' => 'http://www.wikidata.org',
			'dbname' => 'wikidatawiki',
			'code' => 'wikidata'
		);

		$specials = array(
			'specials' => $specialSites
		);

		$siteMatrix = array_merge(
			array( 'count' => 879 ),
			$sites,
			$specials
		);

		$data = array(
			'sitematrix' => $siteMatrix
		);

		return json_encode( $data );
	}

	protected function getSiteData() {
		$siteData = array(
			array(
				'siteid' => 'enwiki',
				'group' => 'wikipedia',
				'url' => 'en.wikipedia.org',
				'lang' => 'en'
			),
			array(
				'siteid' => 'frwiki',
				'group' => 'wikipedia',
				'url' => 'fr.wikipedia.org',
				'lang' => 'fr'
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
			)
		);

		return $siteData;
	}

	public function getSites( array $sitesData, $scriptPath, $articlePath, $protocol = '' ) {
		$sites = [];

		foreach ( $sitesData as $siteData ) {
			$fields = array(
				'globalid' => $siteData['siteid'],
				'type' => 'mediawiki',
				'group' => $siteData['group'],
				'source' => 'local',
				'language' => $siteData['lang'],
				'localids' => [],
				'internalid' => null,
				'data' => array(
					'paths' => array(
						'file_path' => $protocol . '//' . $siteData['url'] . $scriptPath,
						'page_path' => $protocol . '//' . $siteData['url'] . $articlePath
					)
				),
				'forward' => false,
				'config' => []
			);

			$site = new MediaWikiSite();
			$site->unserialize( serialize( $fields ) );
			$siteId = $siteData['siteid'];
			$sites[$siteId] = $site;
		}

		return $sites;
	}

}
