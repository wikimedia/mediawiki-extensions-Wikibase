<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Wikibase\Client\WikibaseClient;
use MediaWiki\MediaWikiServices;

/**
 * Integration test for statsd tracking.
 *
 * @covers \Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary
 * @covers \Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseEntityLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class Scribunto_LuaWikibaseStatsTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseStatsTest';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseStatsTest' => __DIR__ . '/LuaWikibaseStatsTests.lua',
		];
	}

	/**
	 * PHPUnit seems to warn if this is only defined in parent.
	 */
	public function provideLuaData() {
		return parent::provideLuaData();
	}

	/**
	 * @dataProvider provideLuaData
	 */
	public function testLua( $key, $testName, $expected ) {
		$mwServices = MediaWikiServices::getInstance();
		$mwServices->getStatsdDataFactory()->clearData();
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$oldTrackLuaFunctionCallsPerWiki = $settings->getSetting( 'trackLuaFunctionCallsPerWiki' );
		$oldTrackLuaFunctionCallsPerSiteGroup = $settings->getSetting( 'trackLuaFunctionCallsPerSiteGroup' );

		$settings->setSetting( 'trackLuaFunctionCallsPerWiki', true );
		$settings->setSetting( 'trackLuaFunctionCallsPerSiteGroup', false );

		parent::testLua( $key, $testName, $expected );

		$settings->setSetting( 'trackLuaFunctionCallsPerWiki', $oldTrackLuaFunctionCallsPerWiki );
		$settings->setSetting( 'trackLuaFunctionCallsPerSiteGroup', $oldTrackLuaFunctionCallsPerSiteGroup );

		$siteId = $settings->getSetting( 'siteGlobalID' );

		$luaTrackingKeyCount = 0;
		$stats = $mwServices->getStatsdDataFactory()->getData();
		foreach ( $stats as $stat ) {
			if ( strpos( $stat->getKey(), "MediaWiki.$siteId.wikibase.client.scribunto." ) === 0 ) {
				$luaTrackingKeyCount++;
			}
		}

		$this->assertSame( 5, $luaTrackingKeyCount );
	}

}
