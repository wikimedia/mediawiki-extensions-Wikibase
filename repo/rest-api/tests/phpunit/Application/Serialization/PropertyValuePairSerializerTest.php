<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\RestApi\Domain\ReadModel\Value;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairSerializer
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
	public function testSerialize( PropertyValuePair $propertyValuePair, array $expectedSerialization ): void {
		$this->assertEquals(
			$expectedSerialization,
			$this->newSerializer()->serialize( $propertyValuePair )
		);
	}

	public static function serializationProvider(): Generator {
		yield 'no value for string prop' => [
			new PropertyValuePair(
				new Property(
					new NumericPropertyId( self::STRING_PROPERTY_ID ),
					'string'
				),
				new Value( Value::TYPE_NO_VALUE )
			),
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
					'data-type' => 'string',
				],
			],
		];

		yield 'some value for item id prop' => [
			new PropertyValuePair(
				new Property(
					new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ),
					'wikibase-item'
				),
				new Value( Value::TYPE_SOME_VALUE )
			),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::ITEM_ID_PROPERTY_ID,
					'data-type' => 'wikibase-item',
				],
			],
		];

		yield 'string value' => [
			new PropertyValuePair(
				new Property(
					new NumericPropertyId( self::STRING_PROPERTY_ID ),
					'string'
				),
				new Value(
					Value::TYPE_VALUE,
					new StringValue( 'potato' )
				)
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
			new PropertyValuePair(
				new Property(
					new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ),
					'wikibase-item'
				),
				new Value(
					Value::TYPE_VALUE,
					new EntityIdValue( new ItemId( 'Q123' ) )
				)
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
			new PropertyValuePair(
				new Property(
					new NumericPropertyId( self::TIME_PROPERTY_ID ),
					'time'
				),
				new Value(
					Value::TYPE_VALUE,
					new TimeValue( $timestamp, 0, 0, 0, TimeValue::PRECISION_DAY, $calendar )
				)
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
			new PropertyValuePair(
				new Property(
					new NumericPropertyId( self::GLOBECOORDINATE_PROPERTY_ID ),
					'globe-coordinate'
				),
				new Value(
					Value::TYPE_VALUE,
					new GlobeCoordinateValue( new LatLongValue( 52.0, 13.0 ), 1 )
				)
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
			new PropertyValuePair(
				new Property(
					new NumericPropertyId( self::DELETED_PROPERTY_ID ),
					null
				),
				new Value( Value::TYPE_SOME_VALUE )
			),
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
		return new PropertyValuePairSerializer();
	}

}
