<?php

namespace Wikibase\Lib\Tests;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use PHPUnit4And6Compat;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\StatsdMissRecordingSimpleCache;

/**
 * @covers \Wikibase\Lib\StatsdMissRecordingSimpleCache
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StatsdMissRecordingSimpleCacheTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testGetIncrementsMetric() {
		// Stats expects to be incremented once
		$stats = $this->getMockForAbstractClass( StatsdDataFactoryInterface::class );
		$stats->expects( $this->once() )
			->method( 'increment' )
			->with( 'statsKey', 1 );

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'get' )
			->with( 'nonexistingkey' )
			->willReturnCallback( function( $a, $default ) {
				return $default;
			} );

		$sot = new StatsdMissRecordingSimpleCache( $innerCache, $stats, 'statsKey' );
		$result = $sot->get( 'nonexistingkey', 'my default' );
		$this->assertEquals( 'my default', $result );
	}

	public function testGetMultipleIncrementsMetric() {
		// Stats expects to be incremented once
		$stats = $this->getMockForAbstractClass( StatsdDataFactoryInterface::class );
		$stats->expects( $this->once() )
			->method( 'increment' )
			->with( 'statsKey', 2 );

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'getMultiple' )
			->with( [ 'key1', 'key2' ] )
			->willReturnCallback( function( $a, $default ) {
				return [ 'key1' => $default, 'key2' => $default ];
			} );

		$sot = new StatsdMissRecordingSimpleCache( $innerCache, $stats, 'statsKey' );
		$result = $sot->getMultiple( [ 'key1', 'key2' ], 'd1' );
		$this->assertEquals( [ 'key1' => 'd1', 'key2' => 'd1' ], $result );
	}

	public function testGetDoesNotIncrementsMetricOnHit() {
		$stats = $this->getMockForAbstractClass( StatsdDataFactoryInterface::class );
		$stats->expects( $this->never() )
			->method( 'increment' );

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'get' )
			->with( 'key' )
			->willReturn( 'cached value' );

		$sot = new StatsdMissRecordingSimpleCache( $innerCache, $stats, 'statsKey' );
		$result = $sot->get( 'key', 'default value' );
		$this->assertEquals( 'cached value', $result );
	}

	public function testGetMultipleDoesNotIncrementMetricsOnHit() {
		// Stats expects to be incremented once
		$stats = $this->getMockForAbstractClass( StatsdDataFactoryInterface::class );
		$stats->expects( $this->once() )
			->method( 'increment' )
			->with( 'statsKey', 1 );

		// Inner cache that returns the default that has been passed to the get method (cache miss)
		$innerCache = $this->getMockForAbstractClass( CacheInterface::class );
		$innerCache->expects( $this->once() )
			->method( 'getMultiple' )
			->with( [ 'key1', 'key2' ] )
			->willReturnCallback( function( $a, $default ) {
				return [ 'key1' => 'cachehit', 'key2' => $default ];
			} );

		$sot = new StatsdMissRecordingSimpleCache( $innerCache, $stats, 'statsKey' );
		$result = $sot->getMultiple( [ 'key1', 'key2' ], 'd1' );
		$this->assertEquals( [ 'key1' => 'cachehit', 'key2' => 'd1' ], $result );
	}

}
