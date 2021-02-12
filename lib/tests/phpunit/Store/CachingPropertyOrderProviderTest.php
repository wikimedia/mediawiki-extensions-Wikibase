<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\PropertyOrderProvider;

/**
 * @covers \Wikibase\Lib\Store\CachingPropertyOrderProvider
 *
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class CachingPropertyOrderProviderTest extends \PHPUnit\Framework\TestCase {

	public function testGetPropertyOrder_cacheHit() {
		$expected = [ 'P12' => 1, 'P42' => 2 ];

		$cache = new HashBagOStuff();
		$cache->set( $cache->makeKey( 'wikibase-PropertyOrderProvider' ), $expected );

		$cachingPropertyOrderProvider = new CachingPropertyOrderProvider(
			$this->createMock( PropertyOrderProvider::class ),
			$cache,
			123
		);

		$this->assertSame(
			$expected,
			$cachingPropertyOrderProvider->getPropertyOrder()
		);
	}

	public function testGetPropertyOrder_cacheMiss() {
		$cache = new HashBagOStuff();
		$expected = [ 'P32' => 1, 'P42' => 2 ];

		$provider = $this->createMock( PropertyOrderProvider::class );
		$provider->expects( $this->once() )
			->method( 'getPropertyOrder' )
			->with()
			->willReturn( $expected );

		$cachingPropertyOrderProvider = new CachingPropertyOrderProvider( $provider, $cache, 123 );

		$this->assertSame(
			$expected,
			$cachingPropertyOrderProvider->getPropertyOrder()
		);

		// Make sure the new value also made it into the cache
		$this->assertSame(
			$expected,
			$cache->get( $cache->makeKey( 'wikibase-PropertyOrderProvider' ) )
		);
	}

}
