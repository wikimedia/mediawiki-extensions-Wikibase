<?php

namespace Wikibase\DataModel\Tests\Entity;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\Entity\EntityIdValue
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group EntityIdTest
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class EntityIdValueTest extends PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$entityId = new ItemId( 'Q123' );
		$entityIdValue = new EntityIdValue( $entityId );
		$this->assertEquals( $entityId, $entityIdValue->getEntityId() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSerialzationRoundtrip( EntityIdValue $id ) {
		$newId = unserialize( serialize( $id ) );

		$this->assertEquals( $id, $newId );
	}

	public function instanceProvider() {
		$ids = array(
			new ItemId( 'Q1' ),
			new ItemId( 'Q42' ),
			new ItemId( 'Q31337' ),
			// Check for 32-bit integer overflow on 32-bit PHP systems.
			new ItemId( 'Q2147483648' ),
			new PropertyId( 'P1' ),
			new PropertyId( 'P42' ),
			new PropertyId( 'P31337' ),
		);

		$argLists = array();

		foreach ( $ids as $id ) {
			$argLists[] = array( new EntityIdValue( $id ) );
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetType( EntityIdValue $id ) {
		$this->assertEquals( 'wikibase-entityid', $id->getType() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetValue( EntityIdValue $id ) {
		$this->assertEquals( $id, $id->getValue() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetSortKey( EntityIdValue $id ) {
		$this->assertInternalType( 'string', $id->getSortKey() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetArrayValueRoundtrip( EntityIdValue $id ) {
		$newId = EntityIdValue::newFromArray( $id->getArrayValue() );

		$this->assertEquals( $id, $newId );
	}

	public function testSerializationCompatibility() {
		$id = new EntityIdValue( new ItemId( 'Q31337' ) );

		// This is the serialization format from when the EntityIdValue was still together with EntityId.
		$this->assertEquals( '["item",31337]', $id->serialize() );
	}

	public function testDeserializationCompatibility() {
		$expected = new EntityIdValue( new ItemId( 'Q31337' ) );

		// This is the serialization format from f5b8b64823ff215c3796a79d916b6eaa65f4be33, version 0.5 alpha.
		$id = unserialize( 'C:39:"Wikibase\DataModel\Entity\EntityIdValue":14:{["item",31337]}' );
		$this->assertEquals( $expected, $id );
	}

	public function testGetArrayValueCompatibility() {
		$id = new EntityIdValue( new ItemId( 'Q31337' ) );

		$this->assertSame(
			// This is the serialization format from when the EntityIdValue was still together with EntityId.
			array(
				'entity-type' => 'item',
				'numeric-id' => (float)31337,
				'id' => 'Q31337',
			),
			$id->getArrayValue()
		);
	}

	/**
	 * @dataProvider validArrayProvider
	 */
	public function testNewFromArrayCompatibility( array $array ) {
		$id = new EntityIdValue( new ItemId( 'Q31337' ) );

		$this->assertEquals( $id, EntityIdValue::newFromArray( $array ) );
	}

	public function validArrayProvider() {
		return array(
			'Legacy format' => array( array(
				'entity-type' => 'item',
				'numeric-id' => 31337,
			) ),
			'Maximum compatibility' => array( array(
				'entity-type' => 'item',
				'numeric-id' => 31337,
				'id' => 'Q31337',
			) ),
		);
	}

	/**
	 * @dataProvider invalidArrayProvider
	 */
	public function testCannotDeserializeInvalidSerialization( $invalidArray ) {
		$this->setExpectedException( 'DataValues\IllegalValueException' );

		EntityIdValue::newFromArray( $invalidArray );
	}

	public function invalidArrayProvider() {
		return array(
			array( null ),

			array( 'foo' ),

			array( array() ),

			'newFromArray can not deserialize' => array( array(
				'id' => 'Q42',
			) ),

			array( array(
				'entity-type' => 'item',
			) ),

			array( array(
				'numeric-id' => 42,
			) ),

			array( array(
				'entity-type' => 'foo',
				'numeric-id' => 42,
			) ),

			array( array(
				'entity-type' => 42,
				'numeric-id' => 42,
			) ),

			array( array(
				'entity-type' => 'item',
				'numeric-id' => -1,
			) ),

			array( array(
				'entity-type' => 'item',
				'numeric-id' => 'foo',
			) ),
		);
	}

}
