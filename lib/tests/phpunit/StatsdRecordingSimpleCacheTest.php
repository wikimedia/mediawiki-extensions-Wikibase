<?php

namespace Wikibase\Lib\Tests;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\StatsdRecordingSimpleCache;

/**
 * @covers \Wikibase\Lib\StatsdRecordingSimpleCache
 *
 * @todo This test needs to be rewritten to use a better way to assert stastd updateCount.
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StatsdRecordingSimpleCacheTest extends \PHPUnit\Framework\TestCase {

	public function testGetIncrementsMetric() {
		// Stats expects to be incremented once
		$stats = $this->getMockForAbstractClass( StatsdDataFactoryInterface::class );
		$stats->expects( $this->once() )
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
		$sot = new StatsdRecordingSimpleCache( $innerCache, $stats, $statsKeys );
		$result = $sot->get( 'nonexistingkey', 'my default' );
		$this->assertEquals( 'my default', $result );
	}

	public function testGetMultipleIncrementsMetric() {
		$stats = $this->getMockForAbstractClass( StatsdDataFactoryInterface::class );
		$stats->expects( $this->atLeast( 2 ) )
			->method( 'updateCount' )
			->withConsecutive(
				[ 'statsKeyMiss', 2 ],
				[ 'statsKeyHit', 1 ]
			);

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'getMultiple' )
			->with( [ 'key', 'key1', 'key2' ] )
			->willReturnCallback( function( $a, $default ) {
				return [ 'key' => 'cachedValue', 'key1' => $default, 'key2' => $default ];
			} );

		$statsKeys = [ 'miss' => 'statsKeyMiss', 'hit' => 'statsKeyHit' ];
		$sot = new StatsdRecordingSimpleCache( $innerCache, $stats, $statsKeys );
		$result = $sot->getMultiple( [ 'key', 'key1', 'key2' ], 'd1' );
		$this->assertEquals( [ 'key1' => 'd1', 'key2' => 'd1', 'key' => 'cachedValue' ], $result );
	}

	public function testGetDoesNotIncrementsMetric() {
		$stats = $this->getMockForAbstractClass( StatsdDataFactoryInterface::class );

		$stats->expects( $this->once() )
			->method( 'updateCount' )
			->with( 'statsKeyHit', 1 );

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'get' )
			->with( 'key' )
			->willReturn( 'cached value' );

		$statsKeys = [ 'miss' => 'statsKeyMiss', 'hit' => 'statsKeyHit' ];
		$sot = new StatsdRecordingSimpleCache( $innerCache, $stats, $statsKeys );
		$result = $sot->get( 'key', 'default value' );
		$this->assertEquals( 'cached value', $result );
	}

	public function testGetMultipleDoesNotIncrementMetrics() {
		$stats = $this->getMockForAbstractClass( StatsdDataFactoryInterface::class );
		$stats->expects( $this->atLeast( 2 ) )
			->method( 'updateCount' )
			->withConsecutive(
				[ 'statsKeyMiss', 1 ],
				[ 'statsKeyHit', 1 ]
			);

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'getMultiple' )
			->with( [ 'key1', 'key2' ] )
			->willReturnCallback( function( $a, $default ) {
				return [ 'key1' => 'cachehit', 'key2' => $default ];
			} );

		$statsKeys = [ 'miss' => 'statsKeyMiss', 'hit' => 'statsKeyHit' ];
		$sot = new StatsdRecordingSimpleCache( $innerCache, $stats, $statsKeys );
		$result = $sot->getMultiple( [ 'key1', 'key2' ], 'd1' );
		$this->assertEquals( [ 'key1' => 'cachehit', 'key2' => 'd1' ], $result );
	}

}
