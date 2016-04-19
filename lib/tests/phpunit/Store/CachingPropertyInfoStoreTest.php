<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\CachingPropertyInfoStore;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyInfoStore;

/**
 * @covers Wikibase\CachingPropertyInfoStore
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibasePropertyInfo
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class CachingPropertyInfoStoreTest extends \MediaWikiTestCase {

	/**
	 * @var PropertyInfoStoreTestHelper
	 */
	private $helper;

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->helper = new PropertyInfoStoreTestHelper( $this, array( $this, 'newCachingPropertyInfoStore' ) );
	}

	public function newCachingPropertyInfoStore() {
		$mock = new MockPropertyInfoStore();
		$cache = new HashBagOStuff();
		return new CachingPropertyInfoStore( $mock, $cache );
	}

	public function provideSetPropertyInfo() {
		return $this->helper->provideSetPropertyInfo();
	}

	/**
	 * @dataProvider provideSetPropertyInfo
	 */
	public function testSetPropertyInfo( PropertyId $id, array $info, $expectedException ) {
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
		$p23 = new PropertyId( 'P23' );
		$p42 = new PropertyId( 'P42' );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );
		$info42 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' );

		$mock = new MockPropertyInfoStore();
		$cache = new HashBagOStuff();

		$mock->setPropertyInfo( $p23, $info23 );

		$store = new CachingPropertyInfoStore( $mock, $cache );

		$this->assertEquals( $info23, $store->getPropertyInfo( $p23 ), "get from source" );
		$this->assertEquals( $info23, $store->getPropertyInfo( $p23 ), "get cached" );

		$store->setPropertyInfo( $p42, $info42 );
		$this->assertEquals( $info42, $store->getPropertyInfo( $p42 ), "cache updated" );
		$this->assertEquals( $info42, $mock->getPropertyInfo( $p42 ), "source updated" );
	}

}
