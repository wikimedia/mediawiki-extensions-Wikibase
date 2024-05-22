<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Deserializers\Exceptions\MissingTypeException;
use Exception;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\SnakValueDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers \Wikibase\DataModel\Deserializers\SnakDeserializer
 *
 * @license GPL-2.0-or-later
 */
class SnakDeserializerTest extends DispatchableDeserializerTestCase {

	private const STRING_PROPERTY_ID = 'P42';

	private PropertyDataTypeLookup $dataTypeLookup;
	private array $deserializerBuilders;
	private array $dataTypeToValueTypeMap;

	protected function setUp(): void {
		parent::setUp();

		$this->dataTypeLookup = new InMemoryDataTypeLookup();
		$this->dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::STRING_PROPERTY_ID ), 'string' );
		$this->deserializerBuilders = [];
		$this->dataTypeToValueTypeMap = [ 'string' => 'string' ];
	}

	protected function buildDeserializer(): SnakDeserializer {
		return new SnakDeserializer(
			new BasicEntityIdParser(),
			new DataValueDeserializer( [
				'string' => StringValue::class,
			] ),
			$this->dataTypeLookup,
			$this->deserializerBuilders,
			$this->dataTypeToValueTypeMap,
			new SnakValueDeserializer( new DataValueDeserializer( [] ), $this->deserializerBuilders )
		);
	}

	public function deserializableProvider(): array {
		return [
			[
				[
					'snaktype' => 'novalue',
					'property' => self::STRING_PROPERTY_ID,
				],
			],
			[
				[
					'snaktype' => 'somevalue',
					'property' => self::STRING_PROPERTY_ID,
				],
			],
			[
				[
					'snaktype' => 'value',
					'property' => self::STRING_PROPERTY_ID,
					'datavalue' => [
						'type' => 'string',
						'value' => 'hax',
					],
				],
			],
		];
	}

	public function nonDeserializableProvider(): array {
		return [
			[
				42,
			],
			[
				[],
			],
			[
				[
					'id' => 'P10',
				],
			],
			[
				[
					'snaktype' => '42value',
				],
			],
		];
	}

	public function deserializationProvider(): array {
		return [
			[
				new PropertyNoValueSnak( 42 ),
				[
					'snaktype' => 'novalue',
					'property' => self::STRING_PROPERTY_ID,
					'hash' => '5c33520fbfb522444868b4168a35d4b919370018',
				],
			],
			[
				new PropertySomeValueSnak( 42 ),
				[
					'snaktype' => 'somevalue',
					'property' => self::STRING_PROPERTY_ID,
				],
			],
			[
				new PropertyValueSnak( 42, new StringValue( 'hax' ) ),
				$this->newStringValueSnakSerialization( self::STRING_PROPERTY_ID, 'hax' ),
			],
			[
				new PropertyNoValueSnak( 42 ),
				[
					'snaktype' => 'novalue',
					'property' => self::STRING_PROPERTY_ID,
					'hash' => 'not a valid hash',
				],
			],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testInvalidSerialization( $serialization ): void {
		$this->expectException( DeserializationException::class );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public static function invalidSerializationProvider(): array {
		return [
			[
				[
					'snaktype' => 'somevalue',
				],
			],
			[
				[
					'snaktype' => 'value',
					'property' => self::STRING_PROPERTY_ID,
				],
			],
		];
	}

	public function testDeserializePropertyIdFilterItemId(): void {
		$this->expectException( InvalidAttributeException::class );
		$this->buildDeserializer()->deserialize( [
			'snaktype' => 'somevalue',
			'property' => 'Q42',
		] );
	}

	public function testDeserializeBadPropertyId(): void {
		$this->expectException( InvalidAttributeException::class );
		$this->buildDeserializer()->deserialize( [
			'snaktype' => 'somevalue',
			'property' => 'xyz',
		] );
	}

	public function testMissingDataValueType_throwsMissingTypeException(): void {
		$this->expectException( MissingTypeException::class );
		$this->buildDeserializer()->deserialize( [
			'snaktype' => 'value',
			'property' => self::STRING_PROPERTY_ID,
			'datavalue' => [
				'value' => 1337,
			],
		] );
	}

	public function testDataValueNotAnArray_throwsMissingTypeException(): void {
		$this->expectException( MissingTypeException::class );
		$this->buildDeserializer()->deserialize( [
			'snaktype' => 'value',
			'property' => self::STRING_PROPERTY_ID,
			'datavalue' => 'not an array',
		] );
	}

	public function testDataValueMissingValue_throwsMissingAttributeException(): void {
		$this->expectException( MissingAttributeException::class );
		$this->buildDeserializer()->deserialize( [
			'snaktype' => 'value',
			'property' => self::STRING_PROPERTY_ID,
			'datavalue' => [
				'type' => 'string',
			],
		] );
	}

	public function testGivenInvalidDataValue_unDeserializableValueIsConstructed(): void {
		$serialization = [
			'snaktype' => 'value',
			'property' => self::STRING_PROPERTY_ID,
			'datavalue' => [
				'type' => 'string',
				'value' => 1337,
			],
		];

		$snak = $this->buildDeserializer()->deserialize( $serialization );

		$this->assertInstanceOf( PropertyValueSnak::class, $snak );
		$this->assertSnakHasUnDeserializableValue( $snak );
	}

	public function testGivenInvalidDataValue_unDeserializableValueWithErrorText(): void {
		$serialization = [
			'snaktype' => 'value',
			'property' => self::STRING_PROPERTY_ID,
			'datavalue' => [
				'type' => 'string',
				'value' => 1337,
				'error' => 'omg, an error!',
			],
		];

		$snak = $this->buildDeserializer()->deserialize( $serialization );

		$expectedValue = new UnDeserializableValue( 1337, 'string', 'omg, an error!' );

		$this->assertTrue( $snak->getDataValue()->equals( $expectedValue ) );
	}

	public function testDataTypeSpecificValueDeserializer(): void {
		$expectedValue = new StringValue( 'value produced by custom deserializer' );
		$dataTypeWithDeserializer = 'some-special-data-type';
		$propertyId = new NumericPropertyId( 'P777' );

		$this->deserializerBuilders = [ "PT:$dataTypeWithDeserializer" => fn() => $expectedValue ];
		$this->dataTypeToValueTypeMap = [ $dataTypeWithDeserializer => 'string' ];
		$this->dataTypeLookup = new InMemoryDataTypeLookup();
		$this->dataTypeLookup->setDataTypeForProperty( $propertyId, $dataTypeWithDeserializer );

		$snak = $this->buildDeserializer()->deserialize(
			$this->newStringValueSnakSerialization( $propertyId, 'potato' )
		);

		$this->assertInstanceOf( PropertyValueSnak::class, $snak );
		$this->assertSame( $expectedValue, $snak->getDataValue() );
	}

	public function testGivenValueParserUnambiguous_doesNoDataTypeLookup(): void {
		$this->dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$this->dataTypeLookup->expects( $this->never() )->method( $this->anything() );
		$expectedValue = 'potato';

		$snak = $this->buildDeserializer()->deserialize(
			$this->newStringValueSnakSerialization( self::STRING_PROPERTY_ID, $expectedValue )
		);

		$this->assertInstanceOf( PropertyValueSnak::class, $snak );
		$this->assertEquals( new StringValue( $expectedValue ), $snak->getDataValue() );
	}

	public function testGivenDataTypeLookupFails_returnsUndeserializableValue(): void {
		$dataTypeWithValueParser = 'some-special-data-type';
		$this->deserializerBuilders = [ "PT:$dataTypeWithValueParser" => fn() => $this->createStub( Deserializer::class ) ];
		$this->dataTypeToValueTypeMap = [ $dataTypeWithValueParser => 'string' ];
		$this->dataTypeLookup = $this->createStub( PropertyDataTypeLookup::class );
		$errorMessage = 'Data type lookup failed';
		$this->dataTypeLookup->method( 'getDataTypeIdForProperty' )->willThrowException( new Exception( $errorMessage ) );
		$expectedValue = 'potato';

		$snak = $this->buildDeserializer()->deserialize(
			$this->newStringValueSnakSerialization( 'P666', $expectedValue )
		);

		$this->assertInstanceOf( PropertyValueSnak::class, $snak );
		$this->assertEquals(
			new UnDeserializableValue( $expectedValue, 'string', $errorMessage ),
			$snak->getDataValue()
		);
	}

	private function newStringValueSnakSerialization( string $propertyId, string $value ): array {
		return [
			'snaktype' => 'value',
			'property' => $propertyId,
			'datavalue' => [
				'type' => 'string',
				'value' => $value,
			],
		];
	}

	private function assertSnakHasUnDeserializableValue( PropertyValueSnak $snak ): void {
		$this->assertEquals( new NumericPropertyId( self::STRING_PROPERTY_ID ), $snak->getPropertyId() );

		$dataValue = $snak->getDataValue();

		/**
		 * @var UnDeserializableValue $dataValue
		 */
		$this->assertInstanceOf( UnDeserializableValue::class, $dataValue );

		$this->assertEquals( 'string', $dataValue->getTargetType() );
		$this->assertEquals( 1337, $dataValue->getValue() );
	}

}
