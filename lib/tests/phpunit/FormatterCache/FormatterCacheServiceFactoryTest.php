<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\FormatterCache;

use BagOStuff;
use CachedBagOStuff;
use IBufferingStatsdDataFactory;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\FormatterCache\FormatterCacheServiceFactory;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;

/**
 * @covers \Wikibase\Lib\FormatterCache\FormatterCacheServiceFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FormatterCacheServiceFactoryTest extends TestCase {

	public function testNewSharedCache() {
		$sut = $this->createSUT();
		$this->assertInstanceOf(
			BagOStuff::class,
			$sut->newSharedCache( CACHE_ANYTHING )
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
		return new FormatterCacheServiceFactory();
	}

}
