<?php

/**
 * @covers SiteMatrixParser
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteMatrixParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider sitesFromJsonProvider
	 */
	public function testSitesFromJson( $scriptPath, $articlePath, $expected ) {
		$json = $this->getSiteMatrixJson();

		$siteMatrixParser = new SiteMatrixParser( $scriptPath, $articlePath, true );

		$sites = $siteMatrixParser->sitesFromJson( $json );

		ksort( $expected );
		ksort( $sites );

		$this->assertEquals( $expected, $sites );
	}

	public function sitesFromJsonProvider() {
		$siteData = $this->getSiteData();

		$data = array();

		$data[] = array(
			'/w/$1',
			'/wiki/$1',
			$this->getSites( $siteData, '/w/$1', '/wiki/$1' )
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

		$specialSites = array();

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

	public function getSites( array $sitesData, $scriptPath, $articlePath ) {
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
						'file_path' => '//' . $siteData['url'] . $scriptPath,
						'page_path' => '//' . $siteData['url'] . $articlePath
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
