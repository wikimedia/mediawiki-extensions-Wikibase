<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use PHPUnit4And6Compat;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\PropertyOrderProvider;

/**
 * @covers Wikibase\Lib\Store\CachingPropertyOrderProvider
 *
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class CachingPropertyOrderProviderTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testGetPropertyOrder_cacheHit() {
		$expected = [ 'P12' => 1, 'P42' => 2 ];

		$cache = new HashBagOStuff();
		$cache->set( wfMemcKey( 'wikibase-PropertyOrderProvider' ), $expected );

		$cachingPropertyOrderProvider = new CachingPropertyOrderProvider(
			$this->getMock( PropertyOrderProvider::class ),
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

		$provider = $this->getMock( PropertyOrderProvider::class );
		$provider->expects( $this->once() )
			->method( 'getPropertyOrder' )
			->with()
			->will( $this->returnValue( $expected ) );

		$cachingPropertyOrderProvider = new CachingPropertyOrderProvider( $provider, $cache, 123 );

		$this->assertSame(
			$expected,
			$cachingPropertyOrderProvider->getPropertyOrder()
		);

		// Make sure the new value also made it into the cache
		$this->assertSame(
			$expected,
			$cache->get( wfMemcKey( 'wikibase-PropertyOrderProvider' ) )
		);
	}

}
