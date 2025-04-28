<?php

namespace Wikibase\Lib\Tests;

use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\StatslibRecordingSimpleCache;
use Wikimedia\Stats\IBufferingStatsdDataFactory;
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
		$dataFactory = $this->getMockForAbstractClass( IBufferingStatsdDataFactory::class );
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();
		$statsFactory->withStatsdDataFactory( $dataFactory );

		$dataFactory->expects( $this->once() )
			->method( 'updateCount' )
			->with( 'statsKey', 1 );

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
		$dataFactory = $this->getMockForAbstractClass( IBufferingStatsdDataFactory::class );
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();
		$statsFactory->withStatsdDataFactory( $dataFactory );

		$expectedArgs = [
			[ 'statsKeyMiss', 2.0 ],
			[ 'statsKeyHit', 1.0 ],
		];

		$dataFactory->expects( $this->atLeast( 2 ) )
			->method( 'updateCount' )
			->willReturnCallback( function ( $key, $delta ) use ( &$expectedArgs ) {
				if ( !$expectedArgs ) {
					return;
				}
				$curExpectedArgs = array_shift( $expectedArgs );
				$this->assertSame( $curExpectedArgs[0], $key );
				$this->assertSame( $curExpectedArgs[1], $delta );
			} );

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
		$dataFactory = $this->getMockForAbstractClass( IBufferingStatsdDataFactory::class );
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();
		$statsFactory->withStatsdDataFactory( $dataFactory );

		$dataFactory->expects( $this->once() )
			->method( 'updateCount' )
			->with( 'statsKeyHit', 1 );

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
		$dataFactory = $this->getMockForAbstractClass( IBufferingStatsdDataFactory::class );
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();
		$statsFactory->withStatsdDataFactory( $dataFactory );

		$expectedArgs = [
			[ 'statsKeyMiss', 1.0 ],
			[ 'statsKeyHit', 1.0 ],
		];

		$dataFactory->expects( $this->atLeast( 2 ) )
			->method( 'updateCount' )
			->willReturnCallback( function ( $key, $delta ) use ( &$expectedArgs ) {
				if ( !$expectedArgs ) {
					return;
				}
				$curExpectedArgs = array_shift( $expectedArgs );
				$this->assertSame( $curExpectedArgs[0], $key );
				$this->assertSame( $curExpectedArgs[1], $delta );
			} );

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
