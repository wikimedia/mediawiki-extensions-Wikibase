<?php

namespace Wikibase\Test;

use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\CachingPropertyInfoStore;
use Wikibase\PropertyInfoStore;
use Wikibase\Item;

/**
 * @covers Wikibase\CachingPropertyInfoStore
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibasePropertyInfo
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CachingPropertyInfoStoreTest extends \MediaWikiTestCase {

	/**
	 * @var PropertyInfoStoreTestHelper
	 */
	public $helper;

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->helper = new PropertyInfoStoreTestHelper( $this, array( $this, 'newCachingPropertyInfoStore' ) );
	}

	public function newCachingPropertyInfoStore() {
		$mock = new MockPropertyInfoStore();
		$cache = new \HashBagOStuff();
		return new CachingPropertyInfoStore( $mock, $cache );
	}

	public function provideSetPropertyInfo() {
		return $this->helper->provideSetPropertyInfo();
	}

	/**
	 * @dataProvider provideSetPropertyInfo
	 */
	public function testSetPropertyInfo( EntityId $id, array $info, $expectedException ) {
		$this->helper->testSetPropertyInfo( $id, $info, $expectedException );
	}

	public function testGetPropertyInfo() {
		$this->helper->testGetPropertyInfo();
	}

	public function testGetAllPropertyInfo() {
		$this->helper->testGetAllPropertyInfo();
	}

	public function testRemovePropertyInfo() {
		$this->helper->testRemovePropertyInfo();
	}

	public function testPropertyInfoWriteThrough() {
		$p23 = new EntityId( Property::ENTITY_TYPE, 23 );
		$q23 = new EntityId( Item::ENTITY_TYPE, 23 );
		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );
		$info42 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' );

		$mock = new MockPropertyInfoStore();
		$cache = new \HashBagOStuff();

		$mock->setPropertyInfo( $p23, $info23 );

		$store = new CachingPropertyInfoStore( $mock, $cache );

		$this->assertEquals( $info23, $store->getPropertyInfo( $p23 ), "get from source" );
		$this->assertEquals( $info23, $store->getPropertyInfo( $p23 ), "get cached" );

		$store->setPropertyInfo( $p42, $info42 );
		$this->assertEquals( $info42, $store->getPropertyInfo( $p42 ), "cache updated" );
		$this->assertEquals( $info42, $mock->getPropertyInfo( $p42 ), "source updated" );
	}

}
