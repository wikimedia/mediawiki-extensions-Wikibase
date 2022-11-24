<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use DataValues\StringValue;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\RestApi\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\PropertyValuePairDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyValuePairDeserializerTest extends TestCase {
	private const STRING_PROPERTY_ID = 'P123';
	private const ITEM_ID_PROPERTY_ID = 'P321';
	private const STRING_URI_PROPERTY_ID = 'https://example.com/P1';

	/**
	 * @dataProvider serializationProvider
	 */
	public function testDeserialize( Snak $expectedSnak, array $serialization ): void {
		$this->assertEquals(
			$expectedSnak,
			$this->newDeserializer()->deserialize( $serialization )
		);
	}

	public function serializationProvider(): Generator {
		yield 'no value for string prop' => [
			new PropertyNoValueSnak( new NumericPropertyId( self::STRING_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			]
		];

		yield 'some value for item id prop' => [
			new PropertySomeValueSnak( new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::ITEM_ID_PROPERTY_ID,
				]
			]
		];

		yield 'value for string prop' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::STRING_PROPERTY_ID ),
				new StringValue( 'I am goat' )
			),
			[
				'value' => [ 'content' => 'I am goat', 'type' => 'value' ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			]
		];

		yield 'value for item id prop' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ),
				new EntityIdValue( new ItemId( 'Q123' ) )
			),
			[
				'value' => [
					'type' => 'value',
					'content' => [
						'id' => 'Q123',
						'entity-type' => 'item',
						'numeric-id' => 123
					],
				],
				'property' => [
					'id' => self::ITEM_ID_PROPERTY_ID,
				]
			]
		];

		yield 'non-numeric property id (e.g. federated property)' => [
			new PropertySomeValueSnak( $this->newUriPropertyId( self::STRING_URI_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::STRING_URI_PROPERTY_ID,
				]
			]
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testDeserializationErrors( string $expectedException, array $serialization ): void {
		$this->expectException( $expectedException );

		$this->newDeserializer()->deserialize( $serialization );
	}

	public function invalidSerializationProvider(): Generator {
		yield 'invalid value field type' => [
			InvalidFieldException::class,
			[
				'value' => 42,
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			]
		];

		yield 'invalid value type field' => [
			InvalidFieldException::class,
			[
				'value' => [ 'content' => 'I am goat', 'type' => 'not-a-value-type' ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			],
		];

		yield 'invalid property field type' => [
			InvalidFieldException::class,
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => 42,
			]
		];

		yield 'invalid property id field' => [
			InvalidFieldException::class,
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [ 'id' => 'not-a-property-id' ],
			]
		];

		yield 'invalid value type field type' => [
			InvalidFieldException::class,
			[
				'value' => [ 'type' => true ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			]
		];

		yield 'invalid value content field for string data-type' => [
			InvalidFieldException::class,
			[
				'value' => [ 'type' => 'value', 'content' => 42 ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			]
		];

		yield 'property id is a valid item id' => [
			InvalidFieldException::class,
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [ 'id' => 'Q123' ],
			]
		];

		yield 'property does not exist' => [
			InvalidFieldException::class,
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [ 'id' => 'P666' ],
			]
		];

		yield 'missing value field' => [
			MissingFieldException::class,
			[
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			]
		];

		yield 'missing value type field' => [
			MissingFieldException::class,
			[
				'value' => [ 'content' => 'I am goat' ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			]
		];

		yield 'missing content field' => [
			MissingFieldException::class,
			[
				'value' => [ 'type' => 'value' ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				]
			]
		];

		yield 'missing property field' => [
			MissingFieldException::class,
			[
				'value' => [ 'type' => 'novalue' ],
			]
		];

		yield 'missing property id field' => [
			MissingFieldException::class,
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [],
			]
		];
	}

	private function newDeserializer(): PropertyValuePairDeserializer {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty(
			new NumericPropertyId( self::STRING_PROPERTY_ID ),
			'string'
		);
		$dataTypeLookup->setDataTypeForProperty(
			new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ),
			'wikibase-item'
		);
		$dataTypeLookup->setDataTypeForProperty(
			$this->newUriPropertyId( self::STRING_URI_PROPERTY_ID ),
			'string'
		);

		$entityIdParser = $this->createStub( EntityIdParser::class );
		$entityIdParser->method( 'parse' )->willReturnCallback( function( $id ) {
			if ( substr( $id, 0, 4 ) === 'http' ) {
				return $this->newUriPropertyId( $id );
			}

			return WikibaseRepo::getEntityIdParser()->parse( $id );
		} );

		return new PropertyValuePairDeserializer(
			$dataTypeLookup,
			[
				'string' => 'string',
				'wikibase-item' => 'wikibase-entityid'
			],
			WikibaseRepo::getDataValueDeserializer(),
			$entityIdParser
		);
	}

	private function newUriPropertyId( string $uri ): PropertyId {
		$id = $this->createStub( PropertyId::class );
		$id->method( 'getEntityType' )->willReturn( Property::ENTITY_TYPE );
		$id->method( 'getSerialization' )->willReturn( $uri );

		return $id;
	}

}
