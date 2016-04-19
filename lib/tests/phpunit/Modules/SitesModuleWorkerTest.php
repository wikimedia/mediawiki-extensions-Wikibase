<?php

namespace Wikibase\Test;

use BagOStuff;
use HashBagOStuff;
use HashSiteStore;
use MediaWikiSite;
use PHPUnit_Framework_TestCase;
use Site;
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
 * @group WikibaseLib
 *
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class SitesModuleWorkerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param Site[] $sites
	 * @param string[] $groups
	 * @param string[] $specialGroups
	 * @param BagOStuff|null $cache
	 *
	 * @return SitesModuleWorker
	 */
	private function newSitesModuleWorker(
		array $sites = array(),
		array $groups = array(),
		array $specialGroups = array(),
		BagOStuff $cache = null
	) {
		$siteStore = new HashSiteStore( $sites );

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
	public function testGetScript(
		array $sites,
		array $groups,
		array $specialGroups,
		$languageCode,
		$expected
	) {
		$worker = $this->newSitesModuleWorker( $sites, $groups, $specialGroups );

		$result = $worker->getScript( $languageCode );

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
			'no sites' => array( array(), array(), array(), 'qqx', '[]' ),
			'no site in sitelinkgroups' => array( array( $site ), array(), array(), 'qqx', '[]' ),
			'single site in sitelinkgroups' => array(
				array( $site ),
				array( 'allowedgroup' ),
				array(),
				'qqx',
				'{"siteid":{"shortName":"","name":"","id":"siteid","pageUrl":"","apiUrl":"",' .
				'"languageCode":null,"group":"allowedgroup"}}'
			),
			'single site in special group' => array(
				array( $site ),
				array( 'special' ),
				array( 'allowedgroup' ),
				'ar',
				'{"siteid":{"shortName":"siteid","name":"siteid","id":"siteid","pageUrl":"",' .
				'"apiUrl":"","languageCode":null,"group":"special"}}'
			),
			'single non-MediaWiki site in sitelinkgroups' => array(
				array( $nonMwSite ),
				array( 'allowedgroup' ),
				array(),
				'qqx',
				'[]'
			),
		);
	}

	/**
	 * @dataProvider getDefinitionSummaryProvider
	 */
	public function testGetDefinitionSummary( array $workerLists ) {
		$results = array();

		// Verify the dataHash

		/** @var SitesModuleWorker[] $workers */
		foreach ( $workerLists as $name => $workers ) {
			foreach ( $workers as $worker ) {
				$summary = $worker->getDefinitionSummary();
				$this->assertCount( 1, $summary );
				$hash = $summary['dataHash'];
				if ( isset( $results[ $name ] ) ) {
					$this->assertEquals(
						$results[ $name ], $hash, 'getDefinitionSummary should return the same data hash for equivalent settings'
					);
				} else {
					$results[ $name ] = $hash;
				}
			}
		}

		$collidingValues = array_diff_key( $results, array_unique( $results ) );
		$this->assertEmpty( $collidingValues, 'Different settings lead to same hash' );
	}

	public function getDefinitionSummaryProvider() {
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

	public function testGetDefinitionSummary_caching() {
		$cacheKey = wfMemcKey( 'wikibase-sites-module-modified-hash' );
		$cache = new HashBagOStuff();
		$worker = $this->newSitesModuleWorker( array(), array( 'foo' ), array(), $cache );

		// Make sure whatever hash is computed ends up in the cache
		$summary = $worker->getDefinitionSummary();
		$this->assertSame( $summary['dataHash'], $cache->get( $cacheKey ) );

		$cache->set( $cacheKey, 'cache all the things!' );

		// Verify that cached results are returned
		$summary = $worker->getDefinitionSummary();
		$this->assertSame( 'cache all the things!', $summary['dataHash'] );
	}

}
