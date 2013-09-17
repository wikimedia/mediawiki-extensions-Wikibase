<?php

/**
 * @covers SiteMatrixParser
 *
 * @since 0.1
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
	public function testSitesFromJson( $json, $expected ) {
		$siteMatrixParser = new SiteMatrixParser( '/w/$1', '/wiki/$1', false );
		$sites = $siteMatrixParser->sitesFromJson( $json );
		$this->assertEquals( ksort( $expected ), ksort( $sites ) );
	}

	public function sitesFromJsonProvider() {
		$json = $this->getSiteMatrixJson();
		$sitesData = $this->getSitesData();
		$sites = $this->getSites( $sitesData );

		return array(
			array( $json, $sites )
		);
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

	protected function getSitesData() {
		$sitesData = array(
			array(
				'id' => 'enwiki',
				'group' => 'wikipedia',
				'lang' => 'en',
				'scriptpath' => 'http://en.wikipedia.org/w/$1',
				'articlepath' => 'http://en.wikipedia.org/wiki/$1'
			),
			array(
				'id' => 'frwiki',
				'group' => 'wikipedia',
				'lang' => 'fr',
				'scriptpath' => 'http://fr.wikipedia.org/w/$1',
				'articlepath' => 'http://fr.wikipedia.org/wiki/$1'
			),
			array(
				'id' => 'enwikivoyage',
				'group' => 'wikivoyage',
				'lang' => 'en',
				'scriptpath' => 'http://en.wikivoyage.org/w/$1',
				'articlepath' => 'http://en.wikivoyage.org/wiki/$1'
			),
			array(
				'id' => 'frwikivoyage',
				'group' => 'wikivoyage',
				'lang' => 'fr',
				'scriptpath' => 'http://fr.wikivoyage.org/w/$1',
				'articlepath' => 'http://fr.wikivoyage.org/wiki/$1'
			),
			array(
				'id' => 'enwikiquote',
				'group' => 'wikiquote',
				'lang' => 'en',
				'scriptpath' => 'http://en.wikiquote.org/w/$1',
				'articlepath' => 'http://en.wikiquote.org/wiki/$1'
			),
			array(
				'id' => 'commonswiki',
				'group' => 'commons',
				'scriptpath' => 'http://www.commons.org/w/$1',
				'articlepath' => 'http://www.commons.org/wiki/$1'
			),
			array(
				'id' => 'wikidatawiki',
				'group' => 'wikidata',
				'scriptpath' => 'http://www.wikidata.org/w/$1',
				'articlepath' => 'http://www.wikidata.org/wiki/$1'
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

			$site->setFilePath( $siteData['scriptpath'] );
			$site->setPagePath( $siteData['articlepath'] );

			$sites[$siteId] = $site;
		}

		return $sites;
	}

}
