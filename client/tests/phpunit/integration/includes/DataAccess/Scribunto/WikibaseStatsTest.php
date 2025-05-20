<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use Wikibase\Client\WikibaseClient;
use Wikimedia\Stats\StatsFactory;
use Wikimedia\Stats\UnitTestingHelper;

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

	private UnitTestingHelper $unitTestingHelper;
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

		$this->unitTestingHelper = StatsFactory::newUnitTestingHelper();
		$this->setService( 'StatsFactory', $this->unitTestingHelper->getStatsFactory() );

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
		// testLua runs the lua code in self::$moduleName
		if ( $this->luaTestName === null ) {
			parent::assertPostConditions();
			return;
		}

		$settings = WikibaseClient::getSettings();
		$siteId = $settings->getSetting( 'siteGlobalID' );
		$this->assertSame( 5.0, $this->unitTestingHelper->sum(
			"WikibaseClient.Scribunto_Lua_function_calls_total{site=\"$siteId\"}"
		) );

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
