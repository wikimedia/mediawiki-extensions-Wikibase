<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\DispatchingPropertyInfoLookup;
use Wikibase\Lib\Store\DispatchingPropertyInfoStore;
use Wikibase\PropertyInfoStore;

/**
 * @covers Wikibase\Lib\Store\DispatchingPropertyInfoStore
 *
 * @license GPL-2.0+
 */
class DispatchingPropertyInfoStoreTest extends \PHPUnit_Framework_TestCase {

	private function newDispatchingPropertyInfoStore() {
		$localStore = new MockPropertyInfoStore();
		$localStore->setPropertyInfo( new PropertyId( 'P11' ), [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ] );
		$localStore->setPropertyInfo( new PropertyId( 'P12' ), [ PropertyInfoStore::KEY_DATA_TYPE => 'external-id' ] );

		$fooLookup = new MockPropertyInfoStore();
		$fooLookup->setPropertyInfo( new PropertyId( 'foo:P21' ), [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ] );
		$fooLookup->setPropertyInfo( new PropertyId( 'foo:P22' ), [ PropertyInfoStore::KEY_DATA_TYPE => 'external-id' ] );

		return new DispatchingPropertyInfoStore(
			new DispatchingPropertyInfoLookup( [ '' => $localStore, 'foo' => $fooLookup ] ),
			$localStore
		);
	}

	public function testGivenLocalPropertyId_getPropertyInfoReturnsTheInfo() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->assertEquals(
			[ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
			$store->getPropertyInfo( new PropertyId( 'P11' ) )
		);
	}

	public function testGivenNotExistingLocalPropertyId_getPropertyInfoReturnsNull() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->assertNull( $store->getPropertyInfo( new PropertyId( 'P101' ) ) );
	}

	public function testGivenForeignPropertyId_getPropertyInfoReturnsTheInfo() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->assertEquals(
			[ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
			$store->getPropertyInfo( new PropertyId( 'foo:P21' ) )
		);
	}

	public function testGivenNotExistingForeignPropertyId_getPropertyInfoReturnsNull() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->assertNull( $store->getPropertyInfo( new PropertyId( 'foo:P1001' ) ) );
	}

	public function testGivenPropertyIdFormUnknownRepository_getPropertyInfoReturnsNull() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->assertNull( $store->getPropertyInfo( new PropertyId( 'bar:P303' ) ) );
	}

	public function testGetPropertyInfoForDataTypeReturnsInfoFromAllRepositories() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->assertEquals(
			[
				'P11' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
				'foo:P21' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
			],
			$store->getPropertyInfoForDataType( 'string' )
		);
	}

	public function testGetAllPropertyInfoInfoFromAllRepositories() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->assertEquals(
			[
				'P11' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
				'P12' => [ PropertyInfoStore::KEY_DATA_TYPE => 'external-id' ],
				'foo:P21' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
				'foo:P22' => [ PropertyInfoStore::KEY_DATA_TYPE => 'external-id' ],
			],
			$store->getAllPropertyInfo()
		);
	}

	public function testGivenLocalPropertyId_setPropertyInfoSetsTheInfo() {
		$store = $this->newDispatchingPropertyInfoStore();

		$store->setPropertyInfo( new PropertyId( 'P11' ), [ PropertyInfoStore::KEY_DATA_TYPE => 'external-id' ] );

		$this->assertEquals(
			[ PropertyInfoStore::KEY_DATA_TYPE => 'external-id' ],
			$store->getPropertyInfo( new PropertyId( 'P11' ) )
		);
	}

	public function testGivenForeignPropertyId_setPropertyInfoThrowsException() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->setExpectedException( InvalidArgumentException::class );

		$store->setPropertyInfo( new PropertyId( 'foo:P21' ), [ PropertyInfoStore::KEY_DATA_TYPE => 'external-id' ] );
	}

	public function testGivenLocalPropertyId_removePropertyInfoRemovesTheInfo() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->assertTrue( $store->removePropertyInfo( new PropertyId( 'P11' ) ) );

		$this->assertNull( $store->getPropertyInfo( new PropertyId( 'P11' ) ) );
	}

	public function testGivenForeignPropertyId_removePropertyInfoThrowsException() {
		$store = $this->newDispatchingPropertyInfoStore();

		$this->setExpectedException( InvalidArgumentException::class );

		$store->removePropertyInfo( new PropertyId( 'foo:P21' ) );
	}

}
