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

	private $oldTrackLuaFunctionCallsPerWiki;
	private $oldTrackLuaFunctionCallsPerSiteGroup;

	protected static $moduleName = 'LuaWikibaseStatsTest';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseStatsTest' => __DIR__ . '/LuaWikibaseStatsTests.lua',
		];
	}

	public function setUp() {
		parent::setUp();

		$mwServices = MediaWikiServices::getInstance();
		$mwServices->getStatsdDataFactory()->clearData();
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$this->oldTrackLuaFunctionCallsPerWiki = $settings->getSetting(
			'trackLuaFunctionCallsPerWiki'
		);
		$this->oldTrackLuaFunctionCallsPerSiteGroup = $settings->getSetting(
			'trackLuaFunctionCallsPerSiteGroup'
		);

		$settings->setSetting( 'trackLuaFunctionCallsPerWiki', true );
		$settings->setSetting( 'trackLuaFunctionCallsPerSiteGroup', false );
	}

	public function tearDown() {
		$mwServices = MediaWikiServices::getInstance();
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$settings->setSetting(
			'trackLuaFunctionCallsPerWiki',
			$this->oldTrackLuaFunctionCallsPerWiki
		);
		$settings->setSetting(
			'trackLuaFunctionCallsPerSiteGroup',
			$this->oldTrackLuaFunctionCallsPerSiteGroup
		);

		$siteId = $settings->getSetting( 'siteGlobalID' );
		$keyPrefix = "MediaWiki.$siteId.wikibase.client.scribunto.";

		$luaTrackingKeyCount = 0;
		$stats = $mwServices->getStatsdDataFactory()->getData();
		foreach ( $stats as $stat ) {
			if ( strpos( $stat->getKey(), $keyPrefix ) === 0 ) {
				$luaTrackingKeyCount++;
			}
		}

		// testLua runs the lua code in self::$moduleName
		if ( strpos( $this->getName(), 'testLua' ) === false ) {
			$this->assertSame( 0, $luaTrackingKeyCount );
		} else {
			$this->assertSame( 5, $luaTrackingKeyCount );
		}

		parent::tearDown();
	}

}
