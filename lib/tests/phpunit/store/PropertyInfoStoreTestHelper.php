<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyInfoStore;

/**
 * Helper for testing PropertyInfoStore implementations
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyInfoStoreTestHelper {

	/**
	 * @var PHPUnit_Framework_TestCase
	 */
	protected $test;

	public function __construct( PHPUnit_Framework_TestCase $testCase, $storeBuilder ) {
		$this->test = $testCase;
		$this->storeBuilder = $storeBuilder;
	}

	/**
	 * @return PropertyInfoStore
	 */
	protected function newPropertyInfoStore() {
		return call_user_func( $this->storeBuilder );
	}

	public static function provideSetPropertyInfo() {
		return array(
			array( // #0: ok
				new PropertyId( 'P23' ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'string' ),
				null
			),
			array( // #1: missing required field
				new PropertyId( 'P23' ),
				[],
				InvalidArgumentException::class
			),
			array( // #2: extra data
				new PropertyId( 'P23' ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ),
				null
			),
		);
	}

	public function testSetPropertyInfo( PropertyId $id, array $info, $expectedException ) {
		if ( $expectedException !== null ) {
			$this->test->setExpectedException( $expectedException );
		}

		$table = $this->newPropertyInfoStore();

		$table->setPropertyInfo( $id, $info );
		$res = $table->getPropertyInfo( $id );

		$this->test->assertSame( $info, $res );
	}

	public function testGetPropertyInfo() {
		$table = $this->newPropertyInfoStore();
		$p23 = new PropertyId( 'p23' );
		$p42 = new PropertyId( 'p42' );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );
		$info42 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' );

		$this->test->assertNull( $table->getPropertyInfo( $p23 ), "not set yet, should be null" );

		$table->setPropertyInfo( $p23, $info23 );

		$this->test->assertNull( $table->getPropertyInfo( $p42 ), "not set yet, should be null" );
		$this->test->assertSame(
			$info23,
			$table->getPropertyInfo( $p23 ),
			'should return what was set'
		);

		$table->setPropertyInfo( $p42, $info42 );

		$this->test->assertSame(
			$info42,
			$table->getPropertyInfo( $p42 ),
			'should return what was set'
		);
		$this->test->assertSame(
			$info23,
			$table->getPropertyInfo( $p23 ),
			'should return what was set'
		);
	}

	public function testGetPropertyInfoForDataType() {
		$table = $this->newPropertyInfoStore();
		$p23 = new PropertyId( 'P23' );
		$p42 = new PropertyId( 'P42' );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );
		$info42 = array( PropertyInfoStore::KEY_DATA_TYPE => 'commonsMedia', 'foo' => 'bar' );

		$this->test->assertSame(
			[],
			$table->getPropertyInfoForDataType( 'commonsMedia' ),
			'should initially be empty'
		);

		$table->setPropertyInfo( $p23, $info23 );
		$this->test->assertSame(
			[],
			$table->getPropertyInfoForDataType( 'commonsMedia' ),
			'after adding one property'
		);

		$table->setPropertyInfo( $p42, $info42 );
		$this->test->assertSame(
			array( 42 => $info42 ),
			$table->getPropertyInfoForDataType( 'commonsMedia' ),
			'after adding the second property'
		);

		$table->removePropertyInfo( $p23 );
		$this->test->assertSame(
			array( 42 => $info42 ),
			$table->getPropertyInfoForDataType( 'commonsMedia' ),
			'after removing one property'
		);

		$table->removePropertyInfo( $p42 );
		$this->test->assertSame(
			[],
			$table->getPropertyInfoForDataType( 'commonsMedia' ),
			'after removing the second property'
		);
	}

	public function testGetAllPropertyInfo() {
		$table = $this->newPropertyInfoStore();
		$p23 = new PropertyId( 'P23' );
		$p42 = new PropertyId( 'P42' );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );
		$info42 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' );

		$this->test->assertSame(
			[],
			$table->getAllPropertyInfo(),
			'should initially be empty'
		);

		$table->setPropertyInfo( $p23, $info23 );
		$this->test->assertSame(
			array( 23 => $info23 ),
			$table->getAllPropertyInfo(),
			'after adding one property'
		);

		$table->setPropertyInfo( $p42, $info42 );
		$this->test->assertSame(
			array( 23 => $info23, 42 => $info42 ),
			$table->getAllPropertyInfo(),
			'after adding the second property'
		);

		$table->removePropertyInfo( $p23 );
		$this->test->assertSame(
			array( 42 => $info42 ),
			$table->getAllPropertyInfo(),
			'after removing one property'
		);
	}

	public function testRemovePropertyInfo() {
		$table = $this->newPropertyInfoStore();
		$p23 = new PropertyId( 'p23' );
		$p42 = new PropertyId( 'p42' );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );

		$table->setPropertyInfo( $p23, $info23 );

		$this->test->assertFalse( $table->removePropertyInfo( $p42 ), "deleted unknown" );
		$this->test->assertTrue( $table->removePropertyInfo( $p23 ), "deleted something" );
		$this->test->assertFalse( $table->removePropertyInfo( $p23 ), "deleted nothing" );

	}

	public function testPropertyInfoPersistance() {
		$p23 = new PropertyId( 'p23' );
		$info23 = array( PropertyInfoStore::KEY_DATA_TYPE => 'string' );

		$table1 = $this->newPropertyInfoStore();
		$table1->setPropertyInfo( $p23, $info23 );

		$table2 = $this->newPropertyInfoStore();
		$this->test->assertSame(
			$info23,
			$table2->getPropertyInfo( $p23 ),
			'should return persisted info'
		);
	}

}
