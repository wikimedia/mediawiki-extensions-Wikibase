<?php

namespace Wikibase\Test;

use BagOStuff;
use HashBagOStuff;
use MediaWikiSite;
use PHPUnit_Framework_TestCase;
use Site;
use SiteList;
use Wikibase\Lib\SitesModuleWorker;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Lib\SitesModuleWorker
 *
 * @uses Xml
 * @uses SiteList
 * @uses Wikibase\SettingsArray
 * @uses Wikibase\Lib\LanguageNameLookup
 *
 * @group Wikibase
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class SitesModuleWorkerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param Site[] $sites
	 * @param string[] $groups
	 * @param string[] $specialGroups
	 * @param BagOStuff $cache
	 *
	 * @return SitesModuleWorker
	 */
	private function newSitesModuleWorker(
		array $sites = array(),
		array $groups = array(),
		array $specialGroups = array(),
		BagOStuff $cache = null
	) {
		$siteStore = $this->getMock( '\SiteStore' );
		$siteStore->expects( $this->any() )
			->method( 'getSites' )
			->will( $this->returnValue( new SiteList( $sites ) ) );

		return new SitesModuleWorker(
			new SettingsArray( array(
				'siteLinkGroups' => $groups,
				'specialSiteLinkGroups' => $specialGroups
			) ),
			$siteStore,
			$cache ?: new HashBagOStuff()
		);
	}

	/**
	 * @uses Wikibase\Test\SitesModuleWorkerTest::newSitesModuleWorker
	 *
	 * @dataProvider getScriptProvider
	 */
	public function testGetScript( array $sites, array $groups, array $specialGroups, $expected ) {
		$worker = $this->newSitesModuleWorker( $sites, $groups, $specialGroups );

		$result = $worker->getScript();

		$this->assertEquals( $result, 'mediaWiki.config.set("wbSiteDetails",' . $expected . ');' );
	}

	public function getScriptProvider() {
		$site = new MediaWikiSite();
		$site->setGlobalId( 'siteid' );
		$site->setGroup( 'allowedgroup' );

		$nonMwSite = new Site();
		$nonMwSite->setGlobalId( 'siteid' );
		$nonMwSite->setGroup( 'allowedgroup' );
		return array(
			'no sites' => array( array(), array(), array(), '[]' ),
			'no site in sitelinkgroups' => array( array( $site ), array(), array(), '[]' ),
			'single site in sitelinkgroups' => array(
				array( $site ),
				array( 'allowedgroup' ),
				array(),
				'{"siteid":{"shortName":"","name":"","id":"siteid","pageUrl":"","apiUrl":"",' .
				'"languageCode":null,"group":"allowedgroup"}}'
			),
			'single site in special group' => array(
				array( $site ),
				array( 'special' ),
				array( 'allowedgroup' ),
				'{"siteid":{"shortName":"siteid","name":"siteid","id":"siteid","pageUrl":"","apiUrl":"",' .
				'"languageCode":null,"group":"special"}}'
			),
			'single non-MediaWiki site in sitelinkgroups' => array(
				array( $nonMwSite ),
				array( 'allowedgroup' ),
				array(),
				'[]'
			),
		);
	}

	/**
	 * @dataProvider getModifiedHashProvider
	 */
	public function testGetModifiedHash( array $workerLists ) {
		$results = array();

		/** @var SitesModuleWorker[] $workers */
		foreach ( $workerLists as $name => $workers ) {
			foreach ( $workers as $worker ) {
				$value = $worker->getModifiedHash();
				if ( isset( $results[ $name ] ) ) {
					$this->assertEquals(
						$results[ $name ], $value, 'getModifiedHash should return the same value for equivalent settings'
					);
				} else {
					$results[ $name ] = $value;
				}
			}
		}

		$collidingValues = array_diff_key( $results, array_unique( $results ) );
		$this->assertEmpty( $collidingValues, 'Different settings lead to same hash' );
	}

	public function getModifiedHashProvider() {
		$site = new MediaWikiSite();
		$site->setGlobalId( 'siteid' );
		$site->setGroup( 'allowedgroup' );

		$site2 = new MediaWikiSite();
		$site2->setGlobalId( 'site2id' );
		$site2->setGroup( 'allowedgroup' );

		$nonMwSite = new Site();
		$nonMwSite->setGlobalId( 'siteid' );
		$nonMwSite->setGroup( 'allowedgroup' );

		return array(
			array(
				array(
					'empty workers' => array(
						$this->newSitesModuleWorker(),
						$this->newSitesModuleWorker(),
					// Should ignore non-MW-sites
					// $this->newSitesModuleWorker( array( $nonMwSite ) ),
					),
					'single site' => array(
						$this->newSitesModuleWorker( array( $site ) ),
						$this->newSitesModuleWorker( array( $site ) ),
					// Should ignore non-MW-sites
					// $this->newSitesModuleWorker( array( $site, $nonMwSite ) ),
					// $this->newSitesModuleWorker( array( $nonMwSite, $site ) )
					),
					'single site with configured group' => array(
						$this->newSitesModuleWorker( array( $site ), array( 'allowedgroup' ) ),
						$this->newSitesModuleWorker( array( $site ), array( 'allowedgroup' ) )
					),
				)
			)
		);
	}

	public function testGetModifiedHash_caching() {
		$cacheKey = wfMemcKey( 'wikibase-sites-module-modified-hash' );
		$cache = new HashBagOStuff();
		$worker = $this->newSitesModuleWorker( array(), array( 'foo' ), array(), $cache );

		// Make sure whatever hash is computed ends up in the cache
		$hash = $worker->getModifiedHash();
		$this->assertSame( $hash, $cache->get( $cacheKey ) );

		$cache->set( $cacheKey, 'cache all the things!' );

		// Verify that cached results are returned
		$hash = $worker->getModifiedHash();
		$this->assertSame( 'cache all the things!', $hash );
	}

}
