<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use Wikibase\Client\DataAccess\Scribunto\LuaFunctionCallTracker;
use Wikimedia\Stats\IBufferingStatsdDataFactory;
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
				[],
				false,
				false,
				1,
			],
			'per site group logging only' => [
				[ 'fancy.wikibase.client.scribunto.wikibase.doStuff.call' ],
				true,
				false,
				1,
			],
			'per wiki logging only' => [
				[ 'defancywiki.wikibase.client.scribunto.wikibase.doStuff.call' ],
				false,
				true,
				1,
			],
			'per wiki and per site group logging' => [
				[
					'defancywiki.wikibase.client.scribunto.wikibase.doStuff.call',
					'fancy.wikibase.client.scribunto.wikibase.doStuff.call',
				],
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
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();
		$statsdFactory = $this->createMock( IBufferingStatsdDataFactory::class );

		$keyBuffer = [];
		$statsdFactory->expects( $this->exactly( count( $expected ) ) )
			->method( 'updateCount' )
				->with( $this->isType( 'string' ), 1 / $trackLuaFunctionCallsSampleRate )
			->willReturnCallback( function ( $key ) use ( &$keyBuffer ) {
				$keyBuffer[] = $key;
			} );

		$statsFactory->withStatsdDataFactory( $statsdFactory );

		$tracker = new LuaFunctionCallTracker(
			$statsFactory,
			'defancywiki',
			'fancy',
			$trackLuaFunctionCallsPerSiteGroup,
			$trackLuaFunctionCallsPerWiki,
			$trackLuaFunctionCallsSampleRate
		);

		$tracker->incrementKey( 'doStuff', 'wikibase' );

		$this->assertEquals( $expected, $keyBuffer );
	}

}
