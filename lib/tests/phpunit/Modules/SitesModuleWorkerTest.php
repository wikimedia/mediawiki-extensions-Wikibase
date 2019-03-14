<?php

namespace Wikibase\Lib\Tests\Modules;

use BagOStuff;
use HashBagOStuff;
use HashSiteStore;
use MediaWikiSite;
use Site;
use Wikibase\Lib\SitesModuleWorker;
use Wikibase\SettingsArray;

/**
 * @covers \Wikibase\Lib\SitesModuleWorker
 *
 * @uses Xml
 * @uses SiteList
 * @uses Wikibase\SettingsArray
 * @uses Wikibase\Lib\LanguageNameLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class SitesModuleWorkerTest extends \PHPUnit\Framework\TestCase {

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
		$site1 = new MediaWikiSite();
		$site1->setGlobalId( 'mywiki' );
		$site1->setGroup( 'allowedgroup' );
		$site1->setLinkPath( 'https://my.test/$1' );

		$site2 = new MediaWikiSite();
		$site2->setGlobalId( 'otherwiki' );
		$site2->setGroup( 'othergroup' );
		$site2->setLinkPath( 'https://other.test/$1' );

		$nonMwSite = new Site();
		$nonMwSite->setGlobalId( 'mysite' );
		$nonMwSite->setGroup( 'allowedgroup' );

		return [
			'no sites' => [ [], [], [], 'qqx', '[]' ],
			'no site in sitelinkgroups' => [ [ $site1, $site2 ], [], [], 'qqx', '[]' ],
			'single site in sitelinkgroups' => [
				[ $site1, $site2 ],
				[ 'allowedgroup', 'othergroup' ],
				[],
				'qqx',
				'{"mywiki":{"shortName":"","name":"","id":"mywiki","pageUrl":"//my.test/$1",' .
				'"apiUrl":"","languageCode":null,"group":"allowedgroup"},' .
				'"otherwiki":{"shortName":"","name":"","id":"otherwiki","pageUrl":"//other.test/$1",' .
				'"apiUrl":"","languageCode":null,"group":"othergroup"}}'
			],
			'single site in special group' => [
				[ $site1 ],
				[ 'special' ],
				[ 'allowedgroup' ],
				'ar',
				'{"mywiki":{"shortName":"mywiki","name":"mywiki","id":"mywiki","pageUrl":"//my.test/$1",' .
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

	public function testGetSpecialSiteLinkGroups_caching() {
		$cache = new HashBagOStuff();

		// Call twice. Expect 1 compute.
		$worker1 = $this->newMockWorker( [ new MediaWikiSite() ], [ Site::GROUP_NONE ], $cache );
		$worker1->expects( $this->once() )->method( 'makeScript' )
			->willReturn( [ 'mock details to use' ] );
		$worker1->getScript( 'qqx' );
		$worker1->getScript( 'qqx' );

		// Call twice on a different instance with same cache. Expect 0 compute.
		$worker2 = $this->newMockWorker( [ new MediaWikiSite() ], [ Site::GROUP_NONE ], $cache );
		$worker2->expects( $this->never() )->method( 'makeScript' );
		$worker1->getScript( 'qqx' );
		$worker1->getScript( 'qqx' );
	}

	/**
	 * @param Site[] $sites
	 * @param BagOStuff $cache
	 * @return SitesModuleWorker
	 */
	private function newMockWorker( array $sites, array $groups, BagOStuff $cache ) {
		return $this->getMockBuilder( SitesModuleWorker::class )
			->setConstructorArgs( [
				new SettingsArray( [
					'siteLinkGroups' => $groups,
					'specialSiteLinkGroups' => [],
				] ),
				new HashSiteStore( $sites ),
				$cache
			] )
			->setMethods( [ 'makeScript' ] )
			->getMock();
	}

}
