<?php

namespace Wikibase\Test;

use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyInfoStore;

use Wikibase\SiteLinkTable;
use Wikibase\Item;

/**
 * Helper for testing PropertyInfoStore implementations
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoStoreTestHelper {

	/**
	 * @var \PHPUnit_Framework_TestCase
	 */
	protected $test;

	public function __construct( \PHPUnit_Framework_TestCase $testCase, $storeBuilder ) {
		$this->test = $testCase;
		$this->storeBuilder = $storeBuilder;
	}

	protected function newPropertyInfoStore() {
		return call_user_func( $this->storeBuilder );
	}

	public static function provideSetPropertyInfo() {
		return array(
			array( // #0: ok
				new EntityId( Property::ENTITY_TYPE, 23 ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'string' ),
				null
			),
			array( // #1: not a property
				new EntityId( Item::ENTITY_TYPE, 23 ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'string' ),
				'InvalidArgumentException'
			),
			array( // #2: missing required field
				new EntityId( Property::ENTITY_TYPE, 23 ),
				array(),
				'InvalidArgumentException'
			),
			array( // #3: extra data
				new EntityId( Property::ENTITY_TYPE, 23 ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ),
				null
			),
		);
	}

	public function testSetPropertyInfo( EntityId $id, array $info, $expectedException ) {
		if ( $expectedException !== null ) {
			$this->test->setExpectedException( $expectedException );
		}

		$table = $this->newPropertyInfoStore();

		$table->setPropertyInfo( $id, $info );
		$res = $table->getPropertyInfo( $id );

		$this->test->assertEquals( $info, $res );
	}

	public function testGetPropertyInfo() {
		$table = $this->newPropertyInfoStore();
		$p23 = new EntityId( Property::ENTITY_TYPE, 23 );
		$q23 = new EntityId( Item::ENTITY_TYPE, 23 );
		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );
		$info42 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' );

		$this->test->assertNull( $table->getPropertyInfo( $p23 ), "not set yet, should be null" );

		$table->setPropertyInfo( $p23, $info23 );

		$this->test->assertNull( $table->getPropertyInfo( $p42 ), "not set yet, should be null" );
		$this->test->assertEquals( $info23, $table->getPropertyInfo( $p23 ), "should return what was set" );

		$table->setPropertyInfo( $p42, $info42 );

		$this->test->assertEquals( $info42, $table->getPropertyInfo( $p42 ), "should return what was set" );
		$this->test->assertEquals( $info23, $table->getPropertyInfo( $p23 ), "should return what was set" );

		$this->test->setExpectedException( 'InvalidArgumentException' );
		$table->getPropertyInfo( $q23 );
	}

	public function testGetAllPropertyInfo() {
		$table = $this->newPropertyInfoStore();
		$p23 = new EntityId( Property::ENTITY_TYPE, 23 );
		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );
		$info42 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' );

		$this->test->assertCount( 0, $table->getAllPropertyInfo(), "should initially be empty" );

		$table->setPropertyInfo( $p23, $info23 );
		$this->test->assertCount( 1, $table->getAllPropertyInfo(), "after adding one property" );

		$table->setPropertyInfo( $p42, $info42 );
		$this->test->assertCount( 2, $table->getAllPropertyInfo(), "after adding the second property" );

		$table->removePropertyInfo( $p23 );
		$this->test->assertCount( 1, $table->getAllPropertyInfo(), "after removing one property" );
	}

	public function testRemovePropertyInfo() {
		$table = $this->newPropertyInfoStore();
		$p23 = new EntityId( Property::ENTITY_TYPE, 23 );
		$q23 = new EntityId( Item::ENTITY_TYPE, 23 );
		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );

		$table->setPropertyInfo( $p23, $info23 );

		$this->test->assertFalse( $table->removePropertyInfo( $p42 ), "deleted unknown" );
		$this->test->assertTrue( $table->removePropertyInfo( $p23 ), "deleted something" );
		$this->test->assertFalse( $table->removePropertyInfo( $p23 ), "deleted nothing" );

		$this->test->setExpectedException( 'InvalidArgumentException' );
		$table->removePropertyInfo( $q23 );

	}

	public function testPropertyInfoPersistance() {
		$p23 = new EntityId( Property::ENTITY_TYPE, 23 );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );

		$table1 = $this->newPropertyInfoStore();
		$table1->setPropertyInfo( $p23, $info23 );

		$table2 = $this->newPropertyInfoStore();
		$this->test->assertEquals( $info23, $table2->getPropertyInfo( $p23 ), "should return persisted info" );
	}

}
