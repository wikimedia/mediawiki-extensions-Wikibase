<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Item;
use Wikibase\Property;

/**
 * @covers Wikibase\DataModel\Entity\EntityId
 *
 * @since 0.3
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group EntityIdTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityIdTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorProvider
	 *
	 * @param string $type
	 * @param integer $serialization
	 */
	public function testConstructor( $type, $serialization ) {
		$id = new EntityId( $type, $serialization );

		$this->assertEquals( $type, $id->getEntityType() );
		$this->assertEquals( strtoupper( $serialization ), $id->getSerialization() );
	}

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( Item::ENTITY_TYPE, 'Q123' );
		$argLists[] = array( Property::ENTITY_TYPE, 'P321' );

		$argLists[] = array( Item::ENTITY_TYPE, 'q123' );
		$argLists[] = array( Property::ENTITY_TYPE, 'p321' );

		return $argLists;
	}

	public function testConstructorWithNumericId() {
		$id = new EntityId( Item::ENTITY_TYPE, 123 );
		$this->assertEquals( $id->getNumericId(), 123 );

		$id = new EntityId( Property::ENTITY_TYPE, 123 );
		$this->assertEquals( $id->getNumericId(), 123 );
	}

	public function instanceProvider() {
		$ids = array();

		foreach ( $this->constructorProvider() as $argList ) {
			$ids[] = array( new EntityId( $argList[0], $argList[1] ) );
		}

		return $ids;
	}

	public function equalityProvider() {
		$argLists = array();

		$types = array(
			Item::ENTITY_TYPE,
			Property::ENTITY_TYPE,
		);

		foreach ( array_values( $types ) as $type ) {
			$id = new EntityId( $type, 42 );

			foreach ( array( 1, 42, 9001 ) as $numericId ) {
				foreach ( $types as $secondType ) {
					$secondId = new EntityId( $secondType, $numericId );

					$matches = $type === $secondType && $numericId === 42;

					$argLists[] = array( $id, $secondId, $matches );
				}
			}
		}

		return $argLists;
	}

	/**
	 * @dataProvider equalityProvider
	 */
	public function testEquals( EntityId $id0, EntityId $id1, $expectedEquals ) {
		$this->assertEquals( $expectedEquals, $id0->equals( $id1 ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testEqualsSimple( EntityId $id ) {
		$this->assertTrue( $id->equals( $id ) );
		$this->assertFalse( $id->equals( $id->getSerialization() ) );
		$this->assertFalse( $id->equals( $id->getEntityType() ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSerializationRoundtrip( EntityId $id ) {
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

	public function testDeserializationCompatibility() {
		$v04serialization = 'C:17:"Wikibase\EntityId":12:{["item",123]}';

		$this->assertEquals(
			new EntityId( 'item', 'q123' ),
			unserialize( $v04serialization )
		);

		$v05serialization = 'C:32:"Wikibase\DataModel\Entity\ItemId":15:{["item","Q123"]}';

		$this->assertEquals(
			new ItemId( 'q123' ),
			unserialize( $v05serialization )
		);
	}

	/**
	 * This test will change when the serialization format changes.
	 * If it is being changed intentionally, the test should be updated.
	 * It is just here to catch unintentional changes.
	 */
	public function testSerializationStability() {
		$v05serialization = 'C:32:"Wikibase\DataModel\Entity\ItemId":15:{["item","Q123"]}';
		$id = new ItemId( 'q123' );

		$this->assertEquals(
			serialize( $id ),
			$v05serialization
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testReturnTypeOfToString( EntityId $id ) {
		$this->assertInternalType( 'string', $id->__toString() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetPrefixedId( EntityId $id ) {
		$this->assertEquals( $id->getSerialization(), $id->getPrefixedId() );
	}

	public function testCannotConstructWithNonStringEntityType() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new EntityId( null, 42 );
	}

	public function testCannotConstructWithInvalidSerialization() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new EntityId( 'item', null );
	}

}
