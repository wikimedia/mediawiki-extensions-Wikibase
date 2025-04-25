<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\TermFallbackCache;

use Iterator;
use ObjectCacheFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatslibRecordingSimpleCache;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\CachedBagOStuff;
use Wikimedia\Stats\StatsFactory;

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
	 * @var StatsFactory|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $statsFactory;

	protected function setUp(): void {
		$this->mockLogger = $this->createMock( LoggerInterface::class );
		$this->statsFactory = $this->createMock( StatsFactory::class );

		parent::setUp();
	}

	public function testTermFallbackCacheFactoryCachesInMemory(): void {
		$sharedCacheType = CACHE_DB;
		$cacheSecret = 'secret';

		$mockSharedCache = $this->createMock( BagOStuff::class );
		$mockInMemoryCache = $this->createMock( CachedBagOStuff::class );
		$mockCache = $this->createMock( SimpleCacheWithBagOStuff::class );
		$mockStatslibRecordingSimpleCache = $this->createMock( StatslibRecordingSimpleCache::class );
		$mockObjectCacheFactory = $this->createMock( ObjectCacheFactory::class );

		$serviceFactory = $this->createMock( TermFallbackCacheServiceFactory::class );

		$serviceFactory->expects( $this->once() )
			->method( 'newSharedCache' )
			->with( $sharedCacheType, $mockObjectCacheFactory )
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
			->method( 'newStatslibRecordingCache' )
			->with(
				$mockCache,
				$this->statsFactory,
				[
					'miss' => 'wikibase.repo.formatterCache.miss',
					'hit' => 'wikibase.repo.formatterCache.hit',
				],
				'formatterCache_total'
			)
			->willReturn( $mockStatslibRecordingSimpleCache );

		$factory = new TermFallbackCacheFactory(
			$sharedCacheType,
			$this->mockLogger,
			$this->statsFactory,
			$cacheSecret,
			$serviceFactory,
			null,
			$mockObjectCacheFactory
		);

		$this->assertInstanceOf( StatslibRecordingSimpleCache::class, $factory->getTermFallbackCache() );
	}

	public function testTermFallbackCacheFactoryDoesNotCacheInMemoryRedundantly(): void {
		$sharedCacheType = CACHE_NONE;
		$cacheSecret = 'secret';

		$mockSharedCache = $this->createMock( CachedBagOStuff::class );
		$mockCache = $this->createMock( SimpleCacheWithBagOStuff::class );
		$mockStatslibRecordingSimpleCache = $this->createMock( StatslibRecordingSimpleCache::class );
		$mockObjectCacheFactory = $this->createMock( ObjectCacheFactory::class );

		$serviceFactory = $this->createMock( TermFallbackCacheServiceFactory::class );

		$serviceFactory->expects( $this->once() )
			->method( 'newSharedCache' )
			->with( $sharedCacheType, $mockObjectCacheFactory )
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
			->method( 'newStatslibRecordingCache' )
			->with(
				$mockCache,
				$this->statsFactory,
				[
					'miss' => 'wikibase.repo.formatterCache.miss',
					'hit' => 'wikibase.repo.formatterCache.hit',
				],
				'formatterCache_total'
			)
			->willReturn( $mockStatslibRecordingSimpleCache );

		$factory = new TermFallbackCacheFactory(
			$sharedCacheType,
			$this->mockLogger,
			$this->statsFactory,
			$cacheSecret,
			$serviceFactory,
			null,
			$mockObjectCacheFactory
		);

		$this->assertInstanceOf( StatslibRecordingSimpleCache::class, $factory->getTermFallbackCache() );
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
			$this->statsFactory,
			$cacheSecret,
			$serviceFactory,
			$version,
			$this->createMock( ObjectCacheFactory::class )
		);

		$this->assertInstanceOf( StatslibRecordingSimpleCache::class, $factory->getTermFallbackCache() );
	}

	public static function provideVersionTestData(): Iterator {
		yield 'no version' => [ null, 'wikibase.repo.formatter.' ];
		yield 'with version' => [ 5, 'wikibase.repo.formatter.5.' ];
	}

}
