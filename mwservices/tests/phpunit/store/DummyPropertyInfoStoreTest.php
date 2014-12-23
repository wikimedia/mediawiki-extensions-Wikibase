<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DummyPropertyInfoStore;
use Wikibase\PropertyInfoStore;

/**
 * @covers Wikibase\DummyPropertyInfoStore
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibasePropertyInfo
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DummyPropertyInfoStoreTest extends \MediaWikiTestCase {

	public function newDummyPropertyInfoStore() {
		return new DummyPropertyInfoStore();
	}

	public function testSetPropertyInfo() {
		$store = $this->newDummyPropertyInfoStore();
		$p23 = new PropertyId( 'P23' );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );

		// just check that there's no exception
		$store->setPropertyInfo( $p23, $info23 );
		$this->assertTrue( true ); // dummy
	}

	public function testGetPropertyInfo() {
		$store = $this->newDummyPropertyInfoStore();
		$p23 = new PropertyId( 'P23' );

		$this->assertNull( $store->getPropertyInfo( $p23 ) );
	}

	public function testGetAllPropertyInfo() {
		$store = $this->newDummyPropertyInfoStore();

		$this->assertCount( 0, $store->getAllPropertyInfo() );
	}

	public function testRemovePropertyInfo() {
		$store = $this->newDummyPropertyInfoStore();
		$p23 = new PropertyId( 'P23' );

		$this->assertFalse( $store->removePropertyInfo( $p23 ) );
	}

}
