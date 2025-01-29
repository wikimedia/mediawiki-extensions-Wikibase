<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\PropertyNotFoundException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\SerializationException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Helpers\TestPropertyValuePairDeserializerFactory;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyValuePairDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyValuePairDeserializerTest extends TestCase {

	private const EXISTING_PROPERTIES_BY_DATA_TYPE = [
		'string' => 'P6308',
		'url' => 'P7527',
		'wikibase-item' => 'P1572',
		'time' => 'P4129',
		'globe-coordinate' => 'P8958',
	];

	/**
	 * @dataProvider validSerializationProvider
	 */
	public function testDeserialize( Snak $expectedSnak, array $serialization ): void {
		$this->assertEquals( $expectedSnak, $this->newDeserializer()->deserialize( $serialization ) );
	}

	public static function validSerializationProvider(): Generator {
		yield 'no value for string property' => [
			new PropertyNoValueSnak( new NumericPropertyId( self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ) ),
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [
					'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ],
				],
			],
		];

		yield 'some value for item id property' => [
			new PropertySomeValueSnak( new NumericPropertyId( self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'wikibase-item' ] ) ),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'wikibase-item' ],
				],
			],
		];

		yield 'value for string property' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ),
				new StringValue( 'potato' )
			),
			[
				'value' => [ 'type' => 'value', 'content' => 'potato' ],
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ],
			],
		];

		yield 'value for url property' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::EXISTING_PROPERTIES_BY_DATA_TYPE['url'] ),
				new StringValue( 'https://example.org' )
			),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE['url'] ],
				'value' => [ 'type' => 'value', 'content' => 'https://example.org' ],
			],
		];

		yield 'value for time property' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::EXISTING_PROPERTIES_BY_DATA_TYPE['time'] ),
				new TimeValue(
					'+2023-11-08T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, TimeValue::CALENDAR_GREGORIAN
				)
			),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE['time'] ],
				'value' => [
					'type' => 'value',
					'content' => [
						'time' => '+2023-11-08T00:00:00Z',
						'precision' => TimeValue::PRECISION_DAY,
						'calendarmodel' => TimeValue::CALENDAR_GREGORIAN,
					],
				],
			],
		];

		yield 'value for globe-coordinate property' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::EXISTING_PROPERTIES_BY_DATA_TYPE['globe-coordinate'] ),
				new GlobeCoordinateValue( new LatLongValue( 14.57718, 4.19741 ), 0.0001 )
			),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE['globe-coordinate'] ],
				'value' => [
					'type' => 'value',
					'content' => [
						'latitude' => 14.57718,
						'longitude' => 4.19741,
						'precision' => 0.0001,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_throwsSerializationException(
		SerializationException $expectedException,
		array $serialization,
		string $basePath
	): void {
		try {
			$this->newDeserializer()->deserialize( $serialization, $basePath );
			$this->fail( 'Expected exception was not thrown.' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function invalidSerializationProvider(): Generator {
		yield 'invalid serialization' => [
			new InvalidFieldException( '', [ 'not', 'an', 'associative', 'array' ], '/some/path' ),
			[ 'not', 'an', 'associative', 'array' ],
			'/some/path',
		];

		yield "invalid 'property' field - int" => [
			new InvalidFieldException( 'property', 42, '/statement/references/3/parts/1/property' ),
			[
				'property' => 42,
				'value' => [ 'type' => 'novalue' ],
			],
			'/statement/references/3/parts/1',
		];

		yield "invalid 'property' field - sequential array" => [
			new InvalidFieldException( 'property', [ 'not an associative array' ], '/some/path/property' ),
			[
				'property' => [ 'not an associative array' ],
				'value' => [ 'type' => 'novalue' ],
			],
			'/some/path',
		];

		yield "invalid 'property/id' field" => [
			new InvalidFieldException( 'id', 'not-a-property-id', '/statements/P789/0/property/id' ),
			[
				'property' => [ 'id' => 'not-a-property-id' ],
				'value' => [ 'type' => 'novalue' ],
			],
			'/statements/P789/0',
		];

		yield "invalid 'property/id' field - item id" => [
			new InvalidFieldException( 'id', 'Q123', '/statements/P789/2/qualifiers/1/property/id' ),
			[
				'property' => [ 'id' => 'Q123' ],
				'value' => [ 'type' => 'novalue' ],
			],
			'/statements/P789/2/qualifiers/1',
		];

		yield "invalid 'value' field - int" => [
			new InvalidFieldException( 'value', 42, '/statements/P789/0/value' ),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ],
				'value' => 42,
			],
			'/statements/P789/0',
		];

		yield "invalid 'value' field - sequential array" => [
			new InvalidFieldException( 'value', [ 'not an associative array' ], '/statement/value' ),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ],
				'value' => [ 'not an associative array' ],
			],
			'/statement',
		];

		yield "invalid 'value/type' field - not one of the allowed value" => [
			new InvalidFieldException( 'type', 'not-a-value-type', '/statements/P789/2/qualifiers/1/value/type' ),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ],
				'value' => [ 'type' => 'not-a-value-type', 'content' => 'I am goat' ],
			],
			'/statements/P789/2/qualifiers/1',
		];

		yield "invalid 'value/content' field" => [
			new InvalidFieldException( 'content', 42, '/statements/P789/3/references/2/parts/1/value/content' ),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ],
				'value' => [ 'type' => 'value', 'content' => 42 ],
			],
			'/statements/P789/3/references/2/parts/1',
		];

		yield "missing 'property' field" => [
			new MissingFieldException( 'property', '/statements/P789/2/qualifiers/1' ),
			[ 'value' => [ 'type' => 'novalue' ] ],
			'/statements/P789/2/qualifiers/1',
		];

		yield "missing 'property/id' field" => [
			new MissingFieldException( 'id', '/statement/references/3/parts/1/property' ),
			[
				'property' => [],
				'value' => [ 'type' => 'novalue' ],
			],
			'/statement/references/3/parts/1',
		];

		yield "missing 'value' field" => [
			new MissingFieldException( 'value', '/some/path' ),
			[ 'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ] ],
			'/some/path',
		];

		yield "missing 'value/type' field" => [
			new MissingFieldException( 'type', '/statements/P789/0/value' ),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ],
				'value' => [ 'content' => 'I am goat' ],
			],
			'/statements/P789/0',
		];

		yield "missing 'value/type' field - empty array" => [
			new MissingFieldException( 'type', '/statement/value' ),
			[
				'property' => [ 'id' => self::EXISTING_PROPERTIES_BY_DATA_TYPE[ 'string' ] ],
				'value' => [],
			],
			'/statement',
		];

		yield 'referenced property does not exist' => [
			new PropertyNotFoundException( 'P666', '/statement/references/3/parts/1/property/id' ),
			[
				'property' => [ 'id' => 'P666' ],
				'value' => [ 'type' => 'novalue' ],
			],
			'/statement/references/3/parts/1',
		];
	}

	private function newDeserializer(): PropertyValuePairDeserializer {
		$deserializerFactory = new TestPropertyValuePairDeserializerFactory();
		$deserializerFactory->setDataTypeForProperties( array_flip( self::EXISTING_PROPERTIES_BY_DATA_TYPE ) );

		return $deserializerFactory->createPropertyValuePairDeserializer();
	}

}
