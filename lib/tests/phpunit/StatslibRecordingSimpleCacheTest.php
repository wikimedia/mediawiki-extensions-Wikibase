<?php

namespace Wikibase\Lib\Tests;

use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\StatslibRecordingSimpleCache;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \Wikibase\Lib\StatslibRecordingSimpleCache
 *
 * @todo This test needs to be rewritten to use a better way to assert stastd updateCount.
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StatslibRecordingSimpleCacheTest extends \PHPUnit\Framework\TestCase {

	public function testGetIncrementsMetric() {
		// Stats expects to be incremented once
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'get' )
			->with( 'nonexistingkey' )
			->willReturnCallback( function( $a, $default ) {
				return $default;
			} );

		$statsKeys = [ 'miss' => 'statsKey', 'hit' => 'statsHit' ];
		$sot = new StatslibRecordingSimpleCache( $innerCache, $statsFactory, $statsKeys, 'statsKey_total' );
		$result = $sot->get( 'nonexistingkey', 'my default' );
		$this->assertEquals( 'my default', $result );
	}

	public function testGetMultipleIncrementsMetric() {
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'getMultiple' )
			->with( [ 'key', 'key1', 'key2' ] )
			->willReturnCallback( function( $a, $default ) {
				return [ 'key' => 'cachedValue', 'key1' => $default, 'key2' => $default ];
			} );

		$statsKeys = [ 'miss' => 'statsKeyMiss', 'hit' => 'statsKeyHit' ];
		$sot = new StatslibRecordingSimpleCache( $innerCache, $statsFactory, $statsKeys, 'statsKey_total' );
		$result = $sot->getMultiple( [ 'key', 'key1', 'key2' ], 'd1' );
		$this->assertEquals( [ 'key1' => 'd1', 'key2' => 'd1', 'key' => 'cachedValue' ], $result );
	}

	public function testGetDoesNotIncrementsMetric() {
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'get' )
			->with( 'key' )
			->willReturn( 'cached value' );

		$statsKeys = [ 'miss' => 'statsKeyMiss', 'hit' => 'statsKeyHit' ];
		$sot = new StatslibRecordingSimpleCache( $innerCache, $statsFactory, $statsKeys, 'statsKey_total' );
		$result = $sot->get( 'key', 'default value' );
		$this->assertEquals( 'cached value', $result );
	}

	public function testGetMultipleDoesNotIncrementMetrics() {
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();

		$expectedArgs = [
			[ 'statsKeyMiss', 1.0 ],
			[ 'statsKeyHit', 1.0 ],
		];

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'getMultiple' )
			->with( [ 'key1', 'key2' ] )
			->willReturnCallback( function( $a, $default ) {
				return [ 'key1' => 'cachehit', 'key2' => $default ];
			} );

		$statsKeys = [ 'miss' => 'statsKeyMiss', 'hit' => 'statsKeyHit' ];
		$sot = new StatslibRecordingSimpleCache( $innerCache, $statsFactory, $statsKeys, 'statsKey_total' );
		$result = $sot->getMultiple( [ 'key1', 'key2' ], 'd1' );
		$this->assertEquals( [ 'key1' => 'cachehit', 'key2' => 'd1' ], $result );
	}
}
