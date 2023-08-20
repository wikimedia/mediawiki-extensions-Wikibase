<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Modules;

use BagOStuff;
use HashBagOStuff;
use HashSiteStore;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use MediaWikiSite;
use Site;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\Modules\CurrentSiteModule;
use Wikibase\Lib\SettingsArray;

/**
 * @covers \Wikibase\Lib\Modules\CurrentSiteModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Guergana Tzatchkova
 */
class CurrentSiteModuleTest extends \PHPUnit\Framework\TestCase {

	private function getContext(): Context {
		$context = $this->createMock( Context::class );
		$context->method( 'msg' )
			->willReturnCallback( function ( $key ) {
				return new RawMessage( "($key)" );
			} );

		return $context;
	}

	public function testGetScript_structure() {
		$module = $this->newCurrentSiteModule( [], [] );
		$script = $module->getScript( $this->getContext() );
		$this->assertStringStartsWith( 'mw.config.set({"wbCurrentSiteDetails":', $script );
		$this->assertStringEndsWith( '});', $script );
	}

	/**
	 * @dataProvider provideScriptDetails
	 */
	public function testGetScript_details(
		array $sites,
		array $specialGroups,
		$expected
	) {
		$module = $this->newCurrentSiteModule( $sites, $specialGroups );

		$result = $module->getScript( $this->getContext() );
		$this->assertEquals( 'mw.config.set({"wbCurrentSiteDetails":' . $expected . '});', $result );
	}

	public function provideScriptDetails() {
		$site1 = new MediaWikiSite();
		$site1->setGlobalId( 'testSiteID' );
		$site1->setGroup( 'theSiteGroup' );
		$site1->setLinkPath( 'https://my.test/wiki/$1' );
		$site1->setFilePath( 'https://my.test/w/$1' );

		yield 'Unknown site, output contains only the id' => [
			'sites' => [],
			'specialGroups' => [],
			'expected' => '{"id":"testSiteID"}',
		];
		yield 'Known site' => [
			'sites' => [ $site1 ],
			'specialGroups' => [ 'ignoredGroup' ],
			'expected' => '{"shortName":"testSiteID","name":"testSiteID","id":"testSiteID",' .
				'"pageUrl":"//my.test/wiki/$1","apiUrl":"//my.test/w/api.php","languageCode":null,"group":"theSiteGroup"}',
		];
		yield 'Site with special site group' => [
			'sites' => [ $site1 ],
			'specialGroups' => [ 'theSiteGroup' ],
			'expected' => '{"shortName":"(wikibase-sitelinks-sitename-testSiteID)","name":"(wikibase-sitelinks-sitename-testSiteID)",' .
				'"id":"testSiteID","pageUrl":"//my.test/wiki/$1","apiUrl":"//my.test/w/api.php","languageCode":null,"group":"special"}',
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

	/** @dataProvider provideSettingsPairs */
	public function testSettings(
		?SettingsArray $clientSettings,
		?SettingsArray $repoSettings,
		string $expected
	) {
		$site1 = new MediaWikiSite();
		$site1->setGroup( 'group1' );
		$site1->setLinkPath( 'https://one.test/wiki/$1' );
		$site1->setFilePath( 'https://one.test/w/$1' );
		$module = new CurrentSiteModule(
			$clientSettings,
			$repoSettings,
			new HashSiteStore( [ $site1 ] ),
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
				'specialSiteLinkGroups' => [],
				'siteGlobalID' => 'wiki1',
			] ),
			'wiki1',
		];
		yield 'Client only' => [
			new SettingsArray( [
				'specialSiteLinkGroups' => [],
				'siteGlobalID' => 'wiki2',
			] ),
			null,
			'wiki2',
		];
		yield 'Repo overrides Client' => [
			new SettingsArray( [
				'specialSiteLinkGroups' => [],
				'siteGlobalID' => 'ignored',
			] ),
			new SettingsArray( [
				'specialSiteLinkGroups' => [],
				'siteGlobalID' => 'wiki1',
			] ),
			'wiki1',
		];
	}

	private function newCurrentSiteModule( array $sites, array $specials = [] ) {
		$module = new CurrentSiteModule(
			new SettingsArray( [
				'specialSiteLinkGroups' => $specials,
				'siteGlobalID' => 'testSiteID',
			] ),
			null,
			new HashSiteStore( $sites ),
			new HashBagOStuff(),
			new LanguageNameLookupFactory( MediaWikiServices::getInstance()->getLanguageNameUtils() )
		);
		$module->setName( 'test' );
		return $module;
	}

	private function newMockModule( array $sites, array $groups, BagOStuff $cache ) {
		return $this->getMockBuilder( CurrentSiteModule::class )
			->setConstructorArgs( [
				new SettingsArray( [
					'siteLinkGroups' => $groups,
					'specialSiteLinkGroups' => [],
					'siteGlobalID' => 'testSiteID',
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
