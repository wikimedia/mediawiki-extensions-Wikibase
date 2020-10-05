<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests;

use IBufferingStatsdDataFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wikibase\Lib\FormatterCacheFactory;
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
	 * @dataProvider cacheTypeProvider
	 */
	public function testSharedCacheFactoryCachesThings( $sharedCacheType ): void {
		$logger = $this->createMock( LoggerInterface::class );
		$factory = new FormatterCacheFactory(
			$sharedCacheType,
			$logger,
			$this->createMock( IBufferingStatsdDataFactory::class ),
			'secret'
		);

		$cache = $factory->getFormatterCache();
		$cache->set( 'key', $sharedCacheType );

		$this->assertInstanceOf( StatsdRecordingSimpleCache::class, $cache );
		$this->assertEquals( $sharedCacheType, $cache->get( 'key' ) );
	}

	public function cacheTypeProvider(): array {
		return [
			[ CACHE_ANYTHING ],
			[ CACHE_NONE ],
			[ CACHE_DB ],
			[ CACHE_MEMCACHED ],
			[ CACHE_ACCEL ],
		];
	}

}
