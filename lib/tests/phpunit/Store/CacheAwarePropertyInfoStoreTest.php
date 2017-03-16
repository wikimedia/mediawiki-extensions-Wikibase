<?php

namespace Wikibase\Lib\Tests\Store;

use BagOStuff;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Edrsf\PropertyInfoLookup;
use Wikibase\Lib\Store\CacheAwarePropertyInfoStore;

/**
 * @covers Wikibase\Lib\Store\CacheAwarePropertyInfoStore;
 *
 * @group Wikibase
 * @group WikibasePropertyInfo
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 */
class CacheAwarePropertyInfoStoreTest extends \PHPUnit_Framework_TestCase {

	private function newCacheAwarePropertyInfoStore( BagOStuff $cache ) {
		$mockStore = $this->getMock( \Wikibase\Edrsf\PropertyInfoStore::class );
		$mockStore->expects( $this->any() )->method( 'setPropertyInfo' );
		$mockStore->expects( $this->any() )
			->method( 'removePropertyInfo' )
			->will(
				$this->returnCallback( function( PropertyId $propertyId ) {
					if ( $propertyId->getSerialization() === 'P100' ) {
						return true;
					}
					return false;
				} )
			);

		/** @var \Wikibase\Edrsf\PropertyInfoStore $mockStore */
		return new CacheAwarePropertyInfoStore( $mockStore, $cache, 3600, __CLASS__ );
	}

	public function testGivenKnownPropertyId_removePropertyInfoUpdatesCacheAndReturnsTrue() {
		$propertyId = new PropertyId( 'P100' );

		$cache = $this->getMock( BagOStuff::class );
		$cache->expects( $this->once() )
			->method( 'get' )
			->with( __CLASS__ )
			->will(
				$this->returnValue( [ 'P100' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] ] )
			);
		$cache->expects( $this->once() )
			->method( 'set' )
			->with(
				__CLASS__,
				[],
				$this->isType( 'int' )
			);

		$store = $this->newCacheAwarePropertyInfoStore( $cache );

		$this->assertTrue( $store->removePropertyInfo( $propertyId ) );
	}

	public function testGivenUnknownPropertyId_removePropertyInfoDoesNotTouchCacheAndReturnsFalse() {
		$propertyId = new PropertyId( 'P110' );

		$cache = $this->getMock( BagOStuff::class );
		$cache->expects( $this->never() )->method( 'get' );
		$cache->expects( $this->never() )->method( 'set' );

		$store = $this->newCacheAwarePropertyInfoStore( $cache );

		$this->assertFalse( $store->removePropertyInfo( $propertyId ) );
	}

	public function testSetPropertyInfoUpdatesCache() {
		$propertyId = new PropertyId( 'P111' );

		$cache = $this->getMock( BagOStuff::class );
		$cache->expects( $this->once() )
			->method( 'get' )
			->with( __CLASS__ )
			->will(
				$this->returnValue( [] )
			);
		$cache->expects( $this->once() )
			->method( 'set' )
			->with(
				__CLASS__,
				[ 'P111' => [ \Wikibase\Edrsf\PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] ],
				$this->isType( 'int' )
			);

		$store = $this->newCacheAwarePropertyInfoStore( $cache );

		$store->setPropertyInfo( $propertyId, [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );
	}

	public function testGivenInvalidInfo_setPropertyInfoThrowsException() {
		$cache = $this->getMock( BagOStuff::class );
		$cache->expects( $this->never() )->method( 'get' );
		$cache->expects( $this->never() )->method( 'set' );

		$store = $this->newCacheAwarePropertyInfoStore( $cache );

		$this->setExpectedException( InvalidArgumentException::class );

		$store->setPropertyInfo( new PropertyId( 'P111' ), [ 'foo' => 'bar' ] );
	}

}
