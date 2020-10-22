<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests;

use BagOStuff;
use CachedBagOStuff;
use IBufferingStatsdDataFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wikibase\Lib\FormatterCache\FormatterCacheServiceFactory;
use Wikibase\Lib\FormatterCacheFactory;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;

/**
 * @covers \Wikibase\Lib\FormatterCacheFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FormatterCacheFactoryTest extends TestCase {

	/**
	 * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mockLogger;

	/**
	 * @var IBufferingStatsdDataFactory|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mockIBufferingStatsdDataFactory;

	public function setUp(): void {
		$this->mockLogger = $this->createMock( LoggerInterface::class );
		$this->mockIBufferingStatsdDataFactory = $this->createMock( IBufferingStatsdDataFactory::class );

		parent::setUp();
	}

	public function testFormatterCacheFactoryCachesInMemory(): void {
		$sharedCacheType = CACHE_DB;
		$cacheSecret = 'secret';

		$mockSharedCache = $this->createMock( BagOStuff::class );
		$mockInMemoryCache = $this->createMock( CachedBagOStuff::class );
		$mockCache = $this->createMock( SimpleCacheWithBagOStuff::class );
		$mockStatsdRecordingSimpleCache = $this->createMock( StatsdRecordingSimpleCache::class );

		$serviceFactory = $this->createMock( FormatterCacheServiceFactory::class );

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

		$factory = new FormatterCacheFactory(
			$sharedCacheType,
			$this->mockLogger,
			$this->mockIBufferingStatsdDataFactory,
			$cacheSecret,
			$serviceFactory
		);

		$this->assertInstanceOf( StatsdRecordingSimpleCache::class, $factory->getFormatterCache() );
	}

	public function testFormatterCacheFactoryDoesNotCacheInMemoryRedundantly(): void {
		$sharedCacheType = CACHE_NONE;
		$cacheSecret = 'secret';

		$mockSharedCache = $this->createMock( CachedBagOStuff::class );
		$mockCache = $this->createMock( SimpleCacheWithBagOStuff::class );
		$mockStatsdRecordingSimpleCache = $this->createMock( StatsdRecordingSimpleCache::class );

		$serviceFactory = $this->createMock( FormatterCacheServiceFactory::class );

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

		$factory = new FormatterCacheFactory(
			$sharedCacheType,
			$this->mockLogger,
			$this->mockIBufferingStatsdDataFactory,
			$cacheSecret,
			$serviceFactory
		);

		$this->assertInstanceOf( StatsdRecordingSimpleCache::class, $factory->getFormatterCache() );
	}

}
