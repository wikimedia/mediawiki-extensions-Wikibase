<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use Wikibase\Client\WikibaseClient;

/**
 * Integration test for statsd tracking.
 *
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseLibrary
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseEntityLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class WikibaseStatsTest extends WikibaseLibraryTestCase {

	private bool $oldTrackLuaFunctionCallsPerWiki;
	private bool $oldTrackLuaFunctionCallsPerSiteGroup;
	private bool $oldTrackLuaFunctionCallsSampleRate;

	/** @inheritDoc */
	protected static $moduleName = 'WikibaseStatsTest';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseStatsTest' => __DIR__ . '/WikibaseStatsTests.lua',
		];
	}

	protected function setUp(): void {
		parent::setUp();

		$mwServices = $this->getServiceContainer();
		$mwServices->getStatsdDataFactory()->clearData();
		$settings = WikibaseClient::getSettings();

		$this->oldTrackLuaFunctionCallsPerWiki = $settings->getSetting(
			'trackLuaFunctionCallsPerWiki'
		);
		$this->oldTrackLuaFunctionCallsPerSiteGroup = $settings->getSetting(
			'trackLuaFunctionCallsPerSiteGroup'
		);
		$this->oldTrackLuaFunctionCallsSampleRate = $settings->getSetting(
			'trackLuaFunctionCallsSampleRate'
		);

		$settings->setSetting( 'trackLuaFunctionCallsPerWiki', true );
		$settings->setSetting( 'trackLuaFunctionCallsPerSiteGroup', false );
		$settings->setSetting( 'trackLuaFunctionCallsPerSiteGroup', false );
		$settings->setSetting( 'trackLuaFunctionCallsSampleRate', 1 );
	}

	protected function assertPostConditions(): void {
		$mwServices = $this->getServiceContainer();
		$settings = WikibaseClient::getSettings();

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

		parent::assertPostConditions();
	}

	protected function tearDown(): void {
		$settings = WikibaseClient::getSettings();

		$settings->setSetting(
			'trackLuaFunctionCallsPerWiki',
			$this->oldTrackLuaFunctionCallsPerWiki
		);
		$settings->setSetting(
			'trackLuaFunctionCallsPerSiteGroup',
			$this->oldTrackLuaFunctionCallsPerSiteGroup
		);
		$settings->setSetting(
			'trackLuaFunctionCallsSampleRate',
			$this->oldTrackLuaFunctionCallsSampleRate
		);

		parent::tearDown();
	}

}
