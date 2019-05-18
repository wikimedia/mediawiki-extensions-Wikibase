<?php

namespace Wikibase\Lib\Tests\Modules;

use BagOStuff;
use HashBagOStuff;
use HashSiteStore;
use MediaWikiSite;
use PHPUnit4And6Compat;
use ResourceLoaderContext;
use Site;
use Wikibase\SettingsArray;
use Wikibase\SitesModule;

/**
 * @covers \Wikibase\SitesModule
 *
 * @group Wikibase
 * @group NotLegitUnitTest
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SitesModuleTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @return ResourceLoaderContext
	 */
	private function getContext( $languageCode = 'qqx' ) {
		$context = $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->any() )
			->method( 'getLanguage' )
			->willReturn( $languageCode );

		return $context;
	}

	public function testGetScript_structure() {
		$module = $this->newSitesModule( [], [] );
		$script = $module->getScript( $this->getContext() );
		$this->assertStringStartsWith( 'mw.config.set({"wbSiteDetails":', $script );
		$this->assertStringEndsWith( '});', $script );
	}

	/**
	 * @dataProvider provideScriptDetails
	 */
	public function testGetScript_details(
		array $sites,
		array $groups,
		array $specialGroups,
		$languageCode,
		$expected
	) {
		$module = $this->newSitesModule( $sites, $groups, $specialGroups );

		$result = $module->getScript( $this->getContext( $languageCode ) );
		$this->assertEquals( 'mw.config.set({"wbSiteDetails":' . $expected . '});', $result );
	}

	public function provideScriptDetails() {
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

	public function testGetScript_caching() {
		$cache = new HashBagOStuff();

		// Call twice. Expect 1 compute.
		$module1 = $this->newMockModule( [ new MediaWikiSite() ], [ Site::GROUP_NONE ], $cache );
		$module1->expects( $this->once() )->method( 'makeScript' )
			->willReturn( [ 'mock details to use' ] );
		$module1->getScript( $this->getContext( 'qqx' ) );
		$module1->getScript( $this->getContext( 'qqx' ) );

		// Call twice on a different instance with same cache. Expect 0 compute.
		$module2 = $this->newMockModule( [ new MediaWikiSite() ], [ Site::GROUP_NONE ], $cache );
		$module2->expects( $this->never() )->method( 'makeScript' );
		$module2->getScript( $this->getContext( 'qqx' ) );
		$module2->getScript( $this->getContext( 'qqx' ) );
	}

	public function testGetVersionHash() {
		$moduleLists = $this->getModulesForVersionHash();
		$namesByHash = [];

		/** @var SitesModule[] $moduleLists */
		foreach ( $moduleLists as $name => $modules ) {
			$hashes = [];
			foreach ( $modules as $module ) {
				$hashes[] = $module->getVersionHash( $this->getContext() );
			}
			$this->assertSame(
				array_unique( $hashes ),
				[ $hashes[0] ],
				'same version hash for equivalent settings'
			);

			$namesByHash[ $hashes[0] ][] = $name;
		}

		$namesWithUniqueHash = [];
		foreach ( $namesByHash as $hash => $names ) {
			if ( count( $names ) === 1 ) {
				$namesWithUniqueHash[] = $names[0];
			}
		}

		$this->assertSame(
			array_keys( $moduleLists ),
			$namesWithUniqueHash,
			'different hash for different settings'
		);
	}

	private function getModulesForVersionHash() {
		$site = new MediaWikiSite();
		$site->setGlobalId( 'siteid' );
		$site->setGroup( 'allowedgroup' );

		return [
			'empty result' => [
				$this->newSitesModule( [], [] ),
				$this->newSitesModule( [], [] ),
				// Same as empty, given $site's group is not specified
				$this->newSitesModule( [ $site ], [] ),
				$this->newSitesModule( [ $site ], [] ),
			],
			'single site with configured group' => [
				$this->newSitesModule( [ $site ], [ 'allowedgroup' ] ),
				$this->newSitesModule( [ $site ], [ 'allowedgroup' ] )
			],
		];
	}

	private function newSitesModule( array $sites, array $groups, array $specials = [] ) {
		return new SitesModule(
			new SettingsArray( [
				'siteLinkGroups' => $groups,
				'specialSiteLinkGroups' => $specials
			] ),
			new HashSiteStore( $sites ),
			new HashBagOStuff()
		);
	}

	private function newMockModule( array $sites, array $groups, BagOStuff $cache ) {
		return $this->getMockBuilder( SitesModule::class )
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
