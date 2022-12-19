<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Modules;

use BagOStuff;
use HashBagOStuff;
use HashSiteStore;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use MediaWikiSite;
use RawMessage;
use Site;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\Modules\SitesModule;
use Wikibase\Lib\SettingsArray;

/**
 * @covers \Wikibase\Lib\Modules\SitesModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SitesModuleTest extends \PHPUnit\Framework\TestCase {

	private function getContext(): Context {
		$context = $this->createMock( Context::class );
		$context->method( 'msg' )
			->willReturnCallback( function ( $key ) {
				return new RawMessage( "($key)" );
			} );

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
		$expected
	) {
		$module = $this->newSitesModule( $sites, $groups, $specialGroups );

		$result = $module->getScript( $this->getContext() );
		$this->assertEquals( 'mw.config.set({"wbSiteDetails":' . $expected . '});', $result );
	}

	public function provideScriptDetails() {
		$site1 = new MediaWikiSite();
		$site1->setGlobalId( 'mywiki' );
		$site1->setGroup( 'allowedgroup' );
		$site1->setLinkPath( 'https://my.test/wiki/$1' );
		$site1->setFilePath( 'https://my.test/w/$1' );

		$site2 = new MediaWikiSite();
		$site2->setGlobalId( 'otherwiki' );
		$site2->setGroup( 'othergroup' );
		$site2->setLinkPath( 'https://other.test/wiki/$1' );
		$site2->setFilePath( 'https://other.test/w/$1' );

		$nonMwSite = new Site();
		$nonMwSite->setGlobalId( 'mysite' );
		$nonMwSite->setGroup( 'allowedgroup' );

		return [
			'no sites' => [ [], [], [], '[]' ],
			'no site in sitelinkgroups' => [ [ $site1, $site2 ], [], [], '[]' ],
			'single site in sitelinkgroups' => [
				[ $site1, $site2 ],
				[ 'allowedgroup', 'othergroup' ],
				[],
				'{"mywiki":{"shortName":"mywiki","name":"mywiki","id":"mywiki",' .
				'"pageUrl":"//my.test/wiki/$1","apiUrl":"//my.test/w/api.php",' .
				'"languageCode":null,"group":"allowedgroup"},' .
				'"otherwiki":{"shortName":"otherwiki","name":"otherwiki","id":"otherwiki",' .
				'"pageUrl":"//other.test/wiki/$1","apiUrl":"//other.test/w/api.php",' .
				'"languageCode":null,"group":"othergroup"}}',
			],
			'single site in special group' => [
				[ $site1 ],
				[ 'special' ],
				[ 'allowedgroup' ],
				'{"mywiki":{"shortName":"(wikibase-sitelinks-sitename-mywiki)",' .
				'"name":"(wikibase-sitelinks-sitename-mywiki)",' .
				'"id":"mywiki","pageUrl":"//my.test/wiki/$1",' .
				'"apiUrl":"//my.test/w/api.php","languageCode":null,"group":"special"}}',
			],
			'single non-MediaWiki site in sitelinkgroups' => [
				[ $nonMwSite ],
				[ 'allowedgroup' ],
				[],
				'[]',
			],
		];
	}

	public function testGetScript_caching() {
		$cache = new HashBagOStuff();

		// Call twice. Expect 1 compute.
		$module1 = $this->newMockModule( [ new MediaWikiSite() ], [ Site::GROUP_NONE ], $cache );
		$module1->expects( $this->once() )->method( 'makeScript' )
			->willReturn( 'mock script' );
		$module1->getScript( $this->getContext() );
		$module1->getScript( $this->getContext() );

		// Call twice on a different instance with same cache. Expect 0 compute.
		$module2 = $this->newMockModule( [ new MediaWikiSite() ], [ Site::GROUP_NONE ], $cache );
		$module2->expects( $this->never() )->method( 'makeScript' );
		$module2->getScript( $this->getContext() );
		$module2->getScript( $this->getContext() );
	}

	public function testGetVersionHash() {
		$moduleLists = $this->getModulesForVersionHash();
		$namesByHash = [];

		/** @var SitesModule[][] $moduleLists */
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

	/** @dataProvider provideSettingsPairs */
	public function testSettings_RepoOnly(
		?SettingsArray $clientSettings,
		?SettingsArray $repoSettings,
		string $expected
	) {
		$site1 = new MediaWikiSite();
		$site1->setGlobalId( 'wiki1' );
		$site1->setGroup( 'group1' );
		$site1->setLinkPath( 'https://one.test/wiki/$1' );
		$site1->setFilePath( 'https://one.test/w/$1' );
		$site2 = new MediaWikiSite();
		$site2->setGlobalId( 'wiki2' );
		$site2->setGroup( 'group2' );
		$site2->setLinkPath( 'https://two.test/wiki/$1' );
		$site2->setFilePath( 'https://two.test/w/$1' );
		$module = new SitesModule(
			$clientSettings,
			$repoSettings,
			new HashSiteStore( [ $site1, $site2 ] ),
			new HashBagOStuff(),
			new LanguageNameLookupFactory( MediaWikiServices::getInstance()->getLanguageNameUtils() )
		);

		$script = $module->getScript( $this->getContext() );

		$this->assertStringContainsString( $expected, $script );
	}

	public function provideSettingsPairs() {
		yield 'Repo only' => [
			null,
			new SettingsArray( [
				'siteLinkGroups' => [ 'group1' ],
				'specialSiteLinkGroups' => [],
			] ),
			'wiki1',
		];
		yield 'Client only' => [
			new SettingsArray( [
				'siteLinkGroups' => [ 'group2' ],
				'specialSiteLinkGroups' => [],
			] ),
			null,
			'wiki2',
		];
		yield 'Repo overrides Client' => [
			new SettingsArray( [
				'siteLinkGroups' => [ 'group2' ],
				'specialSiteLinkGroups' => [],
			] ),
			new SettingsArray( [
				'siteLinkGroups' => [ 'group1' ],
				'specialSiteLinkGroups' => [],
			] ),
			'wiki1',
		];
	}

	private function getModulesForVersionHash() {
		$site = new MediaWikiSite();
		$site->setGlobalId( 'siteid' );
		$site->setGroup( 'allowedgroup' );
		$site->setFilePath( 'https://site.test/w/$1' );

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
				$this->newSitesModule( [ $site ], [ 'allowedgroup' ] ),
			],
		];
	}

	private function newSitesModule( array $sites, array $groups, array $specials = [] ) {
		return new SitesModule(
			new SettingsArray( [
				'siteLinkGroups' => $groups,
				'specialSiteLinkGroups' => $specials,
			] ),
			null,
			new HashSiteStore( $sites ),
			new HashBagOStuff(),
			new LanguageNameLookupFactory( MediaWikiServices::getInstance()->getLanguageNameUtils() )
		);
	}

	private function newMockModule( array $sites, array $groups, BagOStuff $cache ) {
		return $this->getMockBuilder( SitesModule::class )
			->setConstructorArgs( [
				new SettingsArray( [
					'siteLinkGroups' => $groups,
					'specialSiteLinkGroups' => [],
				] ),
				null,
				new HashSiteStore( $sites ),
				$cache,
				new LanguageNameLookupFactory( MediaWikiServices::getInstance()->getLanguageNameUtils() ),
			] )
			->onlyMethods( [ 'makeScript' ] )
			->getMock();
	}

}
