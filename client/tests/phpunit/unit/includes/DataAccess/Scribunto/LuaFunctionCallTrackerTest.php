<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use Wikibase\Client\DataAccess\Scribunto\LuaFunctionCallTracker;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\LuaFunctionCallTracker
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class LuaFunctionCallTrackerTest extends \PHPUnit\Framework\TestCase {

	public static function incrementKeyProvider() {
		return [
			'logging disabled' => [
				// phpcs:ignore Generic.Files.LineLength.TooLong
				[ 'mediawiki.WikibaseClient.Scribunto_Lua_function_calls_total:1|c|#module:wikibase,function_name:doStuff,site:not_tracked,site_group:not_tracked' ],
				false,
				false,
				1,
			],
			'per site group logging only' => [
				// phpcs:ignore Generic.Files.LineLength.TooLong
				[ 'mediawiki.WikibaseClient.Scribunto_Lua_function_calls_total:1|c|#module:wikibase,function_name:doStuff,site:not_tracked,site_group:fancy' ],
				true,
				false,
				1,
			],
			'per wiki logging only' => [
				// phpcs:ignore Generic.Files.LineLength.TooLong
				[ 'mediawiki.WikibaseClient.Scribunto_Lua_function_calls_total:1|c|#module:wikibase,function_name:doStuff,site:defancywiki,site_group:not_tracked' ],
				false,
				true,
				1,
			],
			'per wiki and per site group logging' => [
				// phpcs:ignore Generic.Files.LineLength.TooLong
				[ 'mediawiki.WikibaseClient.Scribunto_Lua_function_calls_total:10|c|#module:wikibase,function_name:doStuff,site:defancywiki,site_group:fancy' ],
				true,
				true,
				0.1,
			],
		];
	}

	/**
	 * @dataProvider incrementKeyProvider
	 */
	public function testIncrementKey( $expected,
		$trackLuaFunctionCallsPerSiteGroup,
		$trackLuaFunctionCallsPerWiki,
		$trackLuaFunctionCallsSampleRate
	) {
		$statsHelper = StatsFactory::newUnitTestingHelper()->withComponent( 'WikibaseClient' );
		$statsFactory = $statsHelper->getStatsFactory();
		$tracker = new LuaFunctionCallTracker(
			$statsFactory,
			'defancywiki',
			'fancy',
			$trackLuaFunctionCallsPerSiteGroup,
			$trackLuaFunctionCallsPerWiki,
			$trackLuaFunctionCallsSampleRate
		);
		$tracker->incrementKey( 'doStuff', 'wikibase' );
		$this->assertSame( $expected, $statsHelper->consumeAllFormatted() );
	}

}
