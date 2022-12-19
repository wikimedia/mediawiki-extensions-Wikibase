<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Deserializers\SnakDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SnakDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		return new SnakDeserializer(
			new BasicEntityIdParser(),
			new DataValueDeserializer( [
				'string' => StringValue::class,
			] )
		);
	}

	public function deserializableProvider() {
		return [
			[
				[
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
			],
			[
				[
					'snaktype' => 'somevalue',
					'property' => 'P42',
				],
			],
			[
				[
					'snaktype' => 'value',
					'property' => 'P42',
					'datavalue' => [
						'type' => 'string',
						'value' => 'hax',
					],
				],
			],
		];
	}

	public function nonDeserializableProvider() {
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

	public function deserializationProvider() {
		return [
			[
				new PropertyNoValueSnak( 42 ),
				[
					'snaktype' => 'novalue',
					'property' => 'P42',
					'hash' => '5c33520fbfb522444868b4168a35d4b919370018',
				],
			],
			[
				new PropertySomeValueSnak( 42 ),
				[
					'snaktype' => 'somevalue',
					'property' => 'P42',
				],
			],
			[
				new PropertyValueSnak( 42, new StringValue( 'hax' ) ),
				[
					'snaktype' => 'value',
					'property' => 'P42',
					'datavalue' => [
						'type' => 'string',
						'value' => 'hax',
					],
				],
			],
			[
				new PropertyNoValueSnak( 42 ),
				[
					'snaktype' => 'novalue',
					'property' => 'P42',
					'hash' => 'not a valid hash',
				],
			],
		];
	}

	/**
	 * @dataProvider invalidDeserializationProvider
	 */
	public function testInvalidSerialization( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function invalidDeserializationProvider() {
		return [
			[
				[
					'snaktype' => 'somevalue',
				],
			],
			[
				[
					'snaktype' => 'value',
					'property' => 'P42',
				],
			],
		];
	}

	public function testDeserializePropertyIdFilterItemId() {
		$deserializer = new SnakDeserializer( new BasicEntityIdParser(), new DataValueDeserializer() );

		$this->expectException( InvalidAttributeException::class );
		$deserializer->deserialize( [
			'snaktype' => 'somevalue',
			'property' => 'Q42',
		] );
	}

	public function testDeserializeBadPropertyId() {
		$deserializer = new SnakDeserializer( new BasicEntityIdParser(), new DataValueDeserializer() );

		$this->expectException( InvalidAttributeException::class );
		$deserializer->deserialize( [
			'snaktype' => 'somevalue',
			'property' => 'xyz',
		] );
	}

	public function testGivenInvalidDataValue_unDeserializableValueIsConstructed() {
		$serialization = [
			'snaktype' => 'value',
			'property' => 'P42',
			'datavalue' => [
				'type' => 'string',
				'value' => 1337,
			],
		];

		$snak = $this->buildDeserializer()->deserialize( $serialization );

		$this->assertInstanceOf( PropertyValueSnak::class, $snak );
		$this->assertSnakHasUnDeserializableValue( $snak );
	}

	public function testGivenInvalidDataValue_unDeserializableValueWithErrorText() {
		$serialization = [
			'snaktype' => 'value',
			'property' => 'P42',
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

	private function assertSnakHasUnDeserializableValue( PropertyValueSnak $snak ) {
		$this->assertEquals( new NumericPropertyId( 'P42' ), $snak->getPropertyId() );

		$dataValue = $snak->getDataValue();

		/**
		 * @var UnDeserializableValue $dataValue
		 */
		$this->assertInstanceOf( UnDeserializableValue::class, $dataValue );

		$this->assertEquals( 'string', $dataValue->getTargetType() );
		$this->assertEquals( 1337, $dataValue->getValue() );
	}

}
