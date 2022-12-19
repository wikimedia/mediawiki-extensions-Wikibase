<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Wikibase\Client\DataAccess\Scribunto\LuaFunctionCallTracker;

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

	public function incrementKeyProvider() {
		return [
			'logging disabled' => [
				[],
				false,
				false,
				1,
			],
			'per site group logging only' => [
				[ 'fancy.wikibase.client.scribunto.doStuff.call' ],
				true,
				false,
				1,
			],
			'per wiki logging only' => [
				[ 'defancywiki.wikibase.client.scribunto.doStuff.call' ],
				false,
				true,
				1,
			],
			'per wiki and per site group logging' => [
				[
					'defancywiki.wikibase.client.scribunto.doStuff.call',
					'fancy.wikibase.client.scribunto.doStuff.call',
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
		$statsdFactory = $this->createMock( StatsdDataFactoryInterface::class );

		$keyBuffer = [];
		$statsdFactory->expects( $this->exactly( count( $expected ) ) )
			->method( 'updateCount' )
				->with( $this->isType( 'string' ), 1 / $trackLuaFunctionCallsSampleRate )
			->willReturnCallback( function ( $key ) use ( &$keyBuffer ) {
				$keyBuffer[] = $key;
			} );

		$tracker = new LuaFunctionCallTracker(
			$statsdFactory,
			'defancywiki',
			'fancy',
			$trackLuaFunctionCallsPerSiteGroup,
			$trackLuaFunctionCallsPerWiki,
			$trackLuaFunctionCallsSampleRate
		);

		$tracker->incrementKey( 'wikibase.client.scribunto.doStuff.call' );

		$this->assertEquals( $expected, $keyBuffer );
	}

}
