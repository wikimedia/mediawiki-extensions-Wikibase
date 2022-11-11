<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use DataValues\StringValue;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\PropertyValuePairSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyValuePairSerializerTest extends TestCase {
	private const STRING_PROPERTY_ID = 'P123';
	private const ITEM_ID_PROPERTY_ID = 'P321';

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialize( Snak $snak, array $expectedSerialization ): void {
		$this->assertEquals(
			$expectedSerialization,
			$this->newSerializer()->serialize( $snak )
		);
	}

	public function serializationProvider(): Generator {
		yield 'no value for string prop' => [
			new PropertyNoValueSnak( new NumericPropertyId( self::STRING_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
					'data-type' => 'string',
				]
			]
		];

		yield 'some value for item id prop' => [
			new PropertySomeValueSnak( new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::ITEM_ID_PROPERTY_ID,
					'data-type' => 'wikibase-item',
				]
			]
		];

		yield 'string value' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::STRING_PROPERTY_ID ),
				new StringValue( 'potato' )
			),
			[
				'value' => [
					'type' => 'value',
					'content' => 'potato',
				],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
					'data-type' => 'string',
				]
			]
		];

		yield 'item id value' => [
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
					'data-type' => 'wikibase-item',
				]
			]
		];
	}

	private function newSerializer(): PropertyValuePairSerializer {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty(
			new NumericPropertyId( self::STRING_PROPERTY_ID ),
			'string'
		);
		$dataTypeLookup->setDataTypeForProperty(
			new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ),
			'wikibase-item'
		);

		return new PropertyValuePairSerializer( $dataTypeLookup );
	}

}
