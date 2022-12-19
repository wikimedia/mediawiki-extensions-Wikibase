<?php

namespace Wikibase\DataModel\Tests\Entity;

use DataValues\IllegalValueException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Fixtures\CustomEntityId;

/**
 * @covers \Wikibase\DataModel\Entity\EntityIdValue
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 * @author Daniel Kinzler
 */
class EntityIdValueTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$entityId = new ItemId( 'Q123' );
		$entityIdValue = new EntityIdValue( $entityId );
		$this->assertEquals( $entityId, $entityIdValue->getEntityId() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSerialzationRoundtrip( EntityIdValue $id ) {
		$serialized = serialize( $id );
		$newId = unserialize( $serialized );

		$this->assertEquals( $id, $newId );
	}

	public function instanceProvider() {
		/** @var EntityId[] $ids */
		$ids = [
			new ItemId( 'Q1' ),
			new ItemId( 'Q2147483647' ),
			new NumericPropertyId( 'P1' ),
			new NumericPropertyId( 'P31337' ),
			new CustomEntityId( 'X567' ),
			new NumericPropertyId( 'foo:P678' ),
		];

		$argLists = [];

		foreach ( $ids as $id ) {
			$argLists[$id->getSerialization()] = [ new EntityIdValue( $id ) ];
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetType( EntityIdValue $id ) {
		$this->assertSame( 'wikibase-entityid', $id->getType() );
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
		$this->assertIsString( $id->getSortKey() );
	}

	public function provideGetArrayValue() {
		return [
			'Q2147483647' => [
				new ItemId( 'Q2147483647' ),
				[
					'entity-type' => 'item',
					'numeric-id' => 2147483647,
					'id' => 'Q2147483647',
				],
			],
			'P31337' => [
				new NumericPropertyId( 'P31337' ),
				[
					'entity-type' => 'property',
					'numeric-id' => 31337,
					'id' => 'P31337',
				],
			],
			'X567' => [
				new CustomEntityId( 'X567' ),
				[
					'entity-type' => 'custom',
					'id' => 'X567',
				],
			],
			'foo:P678' => [
				new NumericPropertyId( 'foo:P678' ),
				[
					'entity-type' => 'property',
					'numeric-id' => 678,
					'id' => 'foo:P678',
				],
			],
		];
	}

	/**
	 * @dataProvider provideGetArrayValue
	 */
	public function testGetArrayValue( EntityId $id, array $expected ) {
		$value = new EntityIdValue( $id );
		$array = $value->getArrayValue();

		$this->assertSame( $expected, $array );
	}

	public function testSerialize() {
		$serialization = 'O:32:"Wikibase\DataModel\Entity\ItemId":1:{s:13:"serialization";s:6:"Q31337";}';
		$id = new EntityIdValue( new ItemId( 'Q31337' ) );

		$this->assertSame(
			$serialization,
			$id->serialize()
		);
	}

	public function provideDeserializationCompatibility() {
		$local = new EntityIdValue( new ItemId( 'Q31337' ) );
		$foreign = new EntityIdValue( new NumericPropertyId( 'foo:P678' ) );
		$custom = new EntityIdValue( new CustomEntityId( 'X567' ) );

		return [
			'local: Version 0.5 alpha (f5b8b64)' => [
				'C:39:"Wikibase\DataModel\Entity\EntityIdValue":14:{["item",31337]}',
				$local,
			],
			'local: Version 7.0 (7fcddfc)' => [
				'C:39:"Wikibase\DataModel\Entity\EntityIdValue":'
					. '50:{C:32:"Wikibase\DataModel\Entity\ItemId":6:{Q31337}}',
				$local,
			],
			'foreign: Version 7.0 (7fcddfc)' => [
				'C:39:"Wikibase\DataModel\Entity\EntityIdValue":'
					. '63:{C:43:"Wikibase\DataModel\Entity\NumericPropertyId":8:{foo:P678}}',
				$foreign,
			],
			'custom: Version 7.0 (7fcddfc): custom' => [
				'C:39:"Wikibase\DataModel\Entity\EntityIdValue":'
					. '58:{C:42:"Wikibase\DataModel\Fixtures\CustomEntityId":4:{X567}}',
				$custom,
			],
			'local 2022-03 PHP 7.4+' => [
				'O:39:"Wikibase\DataModel\Entity\EntityIdValue":'
					. '1:{s:8:"entityId";O:32:"Wikibase\DataModel\Entity\ItemId":1:{s:13:"serialization";s:6:"Q31337";}}',
				$local,
			],
		];
	}

	/**
	 * @dataProvider provideDeserializationCompatibility
	 *
	 * @param string $serialized
	 * @param EntityIdValue $expected
	 */
	public function testDeserializationCompatibility( $serialized, EntityIdValue $expected ) {
		$id = unserialize( $serialized );

		$this->assertEquals( $expected, $id );
	}

	/**
	 * @dataProvider validArrayProvider
	 */
	public function testNewFromArrayCompatibility( array $array ) {
		$id = new EntityIdValue( new ItemId( 'Q31337' ) );

		$this->assertEquals( $id, EntityIdValue::newFromArray( $array ) );
	}

	public function validArrayProvider() {
		return [
			'Legacy format' => [ [
				'entity-type' => 'item',
				'numeric-id' => 31337,
			] ],
			'Maximum compatibility' => [ [
				'entity-type' => 'item',
				'numeric-id' => 31337,
				'id' => 'Q31337',
			] ],
		];
	}

	/**
	 * @dataProvider invalidArrayProvider
	 */
	public function testCannotDeserializeInvalidSerialization( $invalidArray ) {
		$this->expectException( IllegalValueException::class );

		EntityIdValue::newFromArray( $invalidArray );
	}

	public function invalidArrayProvider() {
		return [
			[ null ],

			[ 'foo' ],

			[ [] ],

			'newFromArray can not deserialize' => [ [
				'id' => 'Q42',
			] ],

			[ [
				'entity-type' => 'item',
			] ],

			[ [
				'numeric-id' => 42,
			] ],

			[ [
				'entity-type' => 'foo',
				'numeric-id' => 42,
			] ],

			[ [
				'entity-type' => 42,
				'numeric-id' => 42,
			] ],

			[ [
				'entity-type' => 'item',
				'numeric-id' => -1,
			] ],

			[ [
				'entity-type' => 'item',
				'numeric-id' => 'foo',
			] ],
		];
	}

}
