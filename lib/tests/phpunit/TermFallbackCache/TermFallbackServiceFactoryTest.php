<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\TermFallbackCache;

use ObjectCacheFactory;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatslibRecordingSimpleCache;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\CachedBagOStuff;
use Wikimedia\Stats\IBufferingStatsdDataFactory;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackServiceFactoryTest extends TestCase {

	public function testNewSharedCache() {
		$sut = $this->createSUT();
		$objectCacheFactory = $this->createMock( ObjectCacheFactory::class );
		$this->assertInstanceOf(
			BagOStuff::class,
			$sut->newSharedCache( CACHE_ANYTHING, $objectCacheFactory )
		);
	}

	public function testNewInMemoryCache() {
		$bagOStuff = $this->createMock( BagOStuff::class );

		$sut = $this->createSUT();
		$this->assertInstanceOf(
			CachedBagOStuff::class,
			$sut->newInMemoryCache( $bagOStuff )
		);
	}

	public function testNewCache() {
		$bagOStuff = $this->createMock( BagOStuff::class );

		$sut = $this->createSUT();
		$this->assertInstanceOf(
			SimpleCacheWithBagOStuff::class,
			$sut->newCache( $bagOStuff, 'pre', 'psst' )
		);
	}

	public function testNewStatslibRecordingCache() {
		$cache = $this->createMock( CacheInterface::class );

		$dataFactory = $this->createMock( IBufferingStatsdDataFactory::class );
		$statsHelper = StatsFactory::newUnitTestingHelper();
		$statsFactory = $statsHelper->getStatsFactory();
		$statsFactory->withStatsdDataFactory( $dataFactory );

		$sut = $this->createSUT();
		$this->assertInstanceOf(
			StatslibRecordingSimpleCache::class,
			$sut->newStatslibRecordingCache( $cache, $statsFactory, [ 'miss' => 'sad', 'hit' => 'hey' ], 'cacheKey_total' )
		);
	}

	private function createSUT() {
		return new TermFallbackCacheServiceFactory();
	}

}
