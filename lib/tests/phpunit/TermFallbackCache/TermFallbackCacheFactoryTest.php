<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\TermFallbackCache;

use BagOStuff;
use CachedBagOStuff;
use IBufferingStatsdDataFactory;
use Iterator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikibase\Lib\TermFallbackCacheFactory;

/**
 * @covers \Wikibase\Lib\TermFallbackCacheFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheFactoryTest extends TestCase {

	/**
	 * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mockLogger;

	/**
	 * @var IBufferingStatsdDataFactory|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mockIBufferingStatsdDataFactory;

	protected function setUp(): void {
		$this->mockLogger = $this->createMock( LoggerInterface::class );
		$this->mockIBufferingStatsdDataFactory = $this->createMock( IBufferingStatsdDataFactory::class );

		parent::setUp();
	}

	public function testTermFallbackCacheFactoryCachesInMemory(): void {
		$sharedCacheType = CACHE_DB;
		$cacheSecret = 'secret';

		$mockSharedCache = $this->createMock( BagOStuff::class );
		$mockInMemoryCache = $this->createMock( CachedBagOStuff::class );
		$mockCache = $this->createMock( SimpleCacheWithBagOStuff::class );
		$mockStatsdRecordingSimpleCache = $this->createMock( StatsdRecordingSimpleCache::class );

		$serviceFactory = $this->createMock( TermFallbackCacheServiceFactory::class );

		$serviceFactory->expects( $this->once() )
			->method( 'newSharedCache' )
			->with( $sharedCacheType )
			->willReturn( $mockSharedCache );

		$serviceFactory->expects( $this->once() )
			->method( 'newInMemoryCache' )
			->with( $mockSharedCache )
			->willReturn( $mockInMemoryCache );

		$mockCache->expects( $this->once() )
			->method( 'setLogger' )
			->with( $this->mockLogger );
		$serviceFactory->expects( $this->once() )
			->method( 'newCache' )
			->with( $mockInMemoryCache, 'wikibase.repo.formatter.', $cacheSecret )
			->willReturn( $mockCache );

		$serviceFactory->expects( $this->once() )
			->method( 'newStatsdRecordingCache' )
			->with(
				$mockCache,
				$this->mockIBufferingStatsdDataFactory,
				[
					'miss' => 'wikibase.repo.formatterCache.miss',
					'hit' => 'wikibase.repo.formatterCache.hit',
				]
			)
			->willReturn( $mockStatsdRecordingSimpleCache );

		$factory = new TermFallbackCacheFactory(
			$sharedCacheType,
			$this->mockLogger,
			$this->mockIBufferingStatsdDataFactory,
			$cacheSecret,
			$serviceFactory,
			null
		);

		$this->assertInstanceOf( StatsdRecordingSimpleCache::class, $factory->getTermFallbackCache() );
	}

	public function testTermFallbackCacheFactoryDoesNotCacheInMemoryRedundantly(): void {
		$sharedCacheType = CACHE_NONE;
		$cacheSecret = 'secret';

		$mockSharedCache = $this->createMock( CachedBagOStuff::class );
		$mockCache = $this->createMock( SimpleCacheWithBagOStuff::class );
		$mockStatsdRecordingSimpleCache = $this->createMock( StatsdRecordingSimpleCache::class );

		$serviceFactory = $this->createMock( TermFallbackCacheServiceFactory::class );

		$serviceFactory->expects( $this->once() )
			->method( 'newSharedCache' )
			->with( $sharedCacheType )
			->willReturn( $mockSharedCache );

		$serviceFactory
			->expects( $this->never() )
			->method( 'newInMemoryCache' );

		$mockCache->expects( $this->once() )
			->method( 'setLogger' )
			->with( $this->mockLogger );
		$serviceFactory->expects( $this->once() )
			->method( 'newCache' )
			->with( $mockSharedCache, 'wikibase.repo.formatter.', $cacheSecret )
			->willReturn( $mockCache );

		$serviceFactory->expects( $this->once() )
			->method( 'newStatsdRecordingCache' )
			->with(
				$mockCache,
				$this->mockIBufferingStatsdDataFactory,
				[
					'miss' => 'wikibase.repo.formatterCache.miss',
					'hit' => 'wikibase.repo.formatterCache.hit',
				]
			)
			->willReturn( $mockStatsdRecordingSimpleCache );

		$factory = new TermFallbackCacheFactory(
			$sharedCacheType,
			$this->mockLogger,
			$this->mockIBufferingStatsdDataFactory,
			$cacheSecret,
			$serviceFactory,
			null
		);

		$this->assertInstanceOf( StatsdRecordingSimpleCache::class, $factory->getTermFallbackCache() );
	}

	/**
	 * @dataProvider provideVersionTestData
	 */
	public function testTermFallbackCacheFactoryLeveragesVersionCorrectly( $version, $cacheKey ): void {
		$sharedCacheType = CACHE_DB;
		$cacheSecret = 'secret';

		$mockInMemoryCache = $this->createMock( CachedBagOStuff::class );

		$serviceFactory = $this->createMock( TermFallbackCacheServiceFactory::class );

		$serviceFactory
			->expects( $this->once() )
			->method( 'newInMemoryCache' )
			->willReturn( $mockInMemoryCache );
		$serviceFactory->expects( $this->once() )
			->method( 'newCache' )
			->with( $mockInMemoryCache, $cacheKey, $cacheSecret );

		$factory = new TermFallbackCacheFactory(
			$sharedCacheType,
			$this->mockLogger,
			$this->mockIBufferingStatsdDataFactory,
			$cacheSecret,
			$serviceFactory,
			$version
		);

		$this->assertInstanceOf( StatsdRecordingSimpleCache::class, $factory->getTermFallbackCache() );
	}

	public function provideVersionTestData(): Iterator {
		yield 'no version' => [ null, 'wikibase.repo.formatter.' ];
		yield 'with version' => [ 5, 'wikibase.repo.formatter.5.' ];
	}

}
