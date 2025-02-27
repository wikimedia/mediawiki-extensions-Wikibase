<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\TermFallbackCache;

use ObjectCacheFactory;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\CachedBagOStuff;
use Wikimedia\Stats\IBufferingStatsdDataFactory;

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

	public function testNewStatsdRecordingCache() {
		$cache = $this->createMock( CacheInterface::class );
		$statsdDataFactory = $this->createMock( IBufferingStatsdDataFactory::class );

		$sut = $this->createSUT();
		$this->assertInstanceOf(
			StatsdRecordingSimpleCache::class,
			$sut->newStatsdRecordingCache( $cache, $statsdDataFactory, [ 'miss' => 'sad', 'hit' => 'hey' ] )
		);
	}

	private function createSUT() {
		return new TermFallbackCacheServiceFactory();
	}

}
