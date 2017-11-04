<?php

namespace Wikibase\Lib\Tests\Modules;

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
 *
 * @license GPL-2.0+
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
		array $sites = [],
		array $groups = [],
		array $specialGroups = [],
		BagOStuff $cache = null
	) {
		$siteStore = new HashSiteStore( $sites );

		return new SitesModuleWorker(
			new SettingsArray( [
				'siteLinkGroups' => $groups,
				'specialSiteLinkGroups' => $specialGroups
			] ),
			$siteStore,
			$cache ?: new HashBagOStuff()
		);
	}

	/**
	 * @uses Wikibase\Lib\Tests\Modules\SitesModuleWorkerTest::newSitesModuleWorker
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

		$this->assertEquals( 'mw.config.set({"wbSiteDetails":' . $expected . '});', $result );
	}

	public function getScriptProvider() {
		$site = new MediaWikiSite();
		$site->setGlobalId( 'siteid' );
		$site->setGroup( 'allowedgroup' );

		$nonMwSite = new Site();
		$nonMwSite->setGlobalId( 'siteid' );
		$nonMwSite->setGroup( 'allowedgroup' );

		return [
			'no sites' => [ [], [], [], 'qqx', '[]' ],
			'no site in sitelinkgroups' => [ [ $site ], [], [], 'qqx', '[]' ],
			'single site in sitelinkgroups' => [
				[ $site ],
				[ 'allowedgroup' ],
				[],
				'qqx',
				'{"siteid":{"shortName":"","name":"","id":"siteid","pageUrl":"","apiUrl":"",' .
				'"languageCode":null,"group":"allowedgroup"}}'
			],
			'single site in special group' => [
				[ $site ],
				[ 'special' ],
				[ 'allowedgroup' ],
				'ar',
				'{"siteid":{"shortName":"siteid","name":"siteid","id":"siteid","pageUrl":"",' .
				'"apiUrl":"","languageCode":null,"group":"special"}}'
			],
			'single non-MediaWiki site in sitelinkgroups' => [
				[ $nonMwSite ],
				[ 'allowedgroup' ],
				[],
				'qqx',
				'[]'
			],
		];
	}

	/**
	 * @dataProvider getDefinitionSummaryProvider
	 */
	public function testGetDefinitionSummary( array $workerLists ) {
		$results = [];

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

		return [
			[
				[
					'empty workers' => [
						$this->newSitesModuleWorker(),
						$this->newSitesModuleWorker(),
					// Should ignore non-MW-sites
					// $this->newSitesModuleWorker( array( $nonMwSite ) ),
					],
					'single site' => [
						$this->newSitesModuleWorker( [ $site ] ),
						$this->newSitesModuleWorker( [ $site ] ),
					// Should ignore non-MW-sites
					// $this->newSitesModuleWorker( array( $site, $nonMwSite ) ),
					// $this->newSitesModuleWorker( array( $nonMwSite, $site ) )
					],
					'single site with configured group' => [
						$this->newSitesModuleWorker( [ $site ], [ 'allowedgroup' ] ),
						$this->newSitesModuleWorker( [ $site ], [ 'allowedgroup' ] )
					],
				]
			]
		];
	}

	public function testGetDefinitionSummary_caching() {
		$cacheKey = wfMemcKey( 'wikibase-sites-module-modified-hash' );
		$cache = new HashBagOStuff();
		$worker = $this->newSitesModuleWorker( [], [ 'foo' ], [], $cache );

		// Make sure whatever hash is computed ends up in the cache
		$summary = $worker->getDefinitionSummary();
		$this->assertSame( $summary['dataHash'], $cache->get( $cacheKey ) );

		$cache->set( $cacheKey, 'cache all the things!' );

		// Verify that cached results are returned
		$summary = $worker->getDefinitionSummary();
		$this->assertSame( 'cache all the things!', $summary['dataHash'] );
	}

}
