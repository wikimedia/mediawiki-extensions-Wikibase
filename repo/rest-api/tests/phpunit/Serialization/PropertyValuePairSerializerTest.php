<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use DataValues\TimeValue;
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
	private const TIME_PROPERTY_ID = 'P456';
	private const GLOBECOORDINATE_PROPERTY_ID = 'P678';
	private const DELETED_PROPERTY_ID = 'P987';

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
				],
			],
		];

		yield 'some value for item id prop' => [
			new PropertySomeValueSnak( new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::ITEM_ID_PROPERTY_ID,
					'data-type' => 'wikibase-item',
				],
			],
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
				],
			],
		];

		yield 'item id value' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ),
				new EntityIdValue( new ItemId( 'Q123' ) )
			),
			[
				'value' => [
					'type' => 'value',
					'content' => 'Q123',
				],
				'property' => [
					'id' => self::ITEM_ID_PROPERTY_ID,
					'data-type' => 'wikibase-item',
				],
			],
		];

		$timestamp = '+2022-11-25T00:00:00Z';
		$calendar = 'Q12345';
		yield 'time value' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::TIME_PROPERTY_ID ),
				new TimeValue( $timestamp, 0, 0, 0, TimeValue::PRECISION_DAY, $calendar )
			),
			[
				'value' => [
					'type' => 'value',
					'content' => [
						'time' => $timestamp,
						'precision' => TimeValue::PRECISION_DAY,
						'calendarmodel' => $calendar,
					],
				],
				'property' => [
					'id' => self::TIME_PROPERTY_ID,
					'data-type' => 'time',
				],
			],
		];

		yield 'globecoordinate value' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::GLOBECOORDINATE_PROPERTY_ID ),
				new GlobeCoordinateValue( new LatLongValue( 52.0, 13.0 ), 1 )
			),
			[
				'value' => [
					'type' => 'value',
					'content' => [
						'latitude' => 52.0,
						'longitude' => 13.0,
						'precision' => 1,
						'globe' => 'http://www.wikidata.org/entity/Q2',
					],
				],
				'property' => [
					'id' => self::GLOBECOORDINATE_PROPERTY_ID,
					'data-type' => 'globe-coordinate',
				],
			],
		];

		yield 'null data type for some property value' => [
			new PropertySomeValueSnak( new NumericPropertyId( self::DELETED_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::DELETED_PROPERTY_ID,
					'data-type' => null,
				],
			],
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
		$dataTypeLookup->setDataTypeForProperty(
			new NumericPropertyId( self::TIME_PROPERTY_ID ),
			'time'
		);
		$dataTypeLookup->setDataTypeForProperty(
			new NumericPropertyId( self::GLOBECOORDINATE_PROPERTY_ID ),
			'globe-coordinate'
		);

		return new PropertyValuePairSerializer( $dataTypeLookup );
	}

}
