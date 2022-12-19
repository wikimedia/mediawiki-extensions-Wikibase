<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacySnakDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$dataValueDeserializer = $this->createMock( Deserializer::class );

		$dataValueDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [ 'type' => 'string', 'value' => 'foo' ] ) )
			->will( $this->returnValue( new StringValue( 'foo' ) ) );

		$this->deserializer = new LegacySnakDeserializer( $dataValueDeserializer );
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],
			[ [] ],
			[ [ 'novalue' ] ],
			[ [ 1337, 'novalue' ] ],
			[ [ 'spam', 1337 ] ],
			[ [ 'novalue', 'daah' ] ],
			[ [ 'novalue', 0 ] ],
			[ [ 'novalue', -1337 ] ],
			[ [ 'novalue', 1337, 'spam' ] ],
			[ [ 'value', 1337 ] ],
			[ [ 'value', 1337, 'data-value-type' ] ],
			[ [ 'value', 1337, 'data-value-type', 'data-value-value', 'spam' ] ],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->deserializer->deserialize( $serialization );
	}

	public function testNoValueSnakDeserialization() {
		$this->assertEquals(
			new PropertyNoValueSnak( 42 ),
			$this->deserializer->deserialize( [
				'novalue',
				42,
			] )
		);
	}

	public function testSomeValueSnakDeserialization() {
		$this->assertEquals(
			new PropertySomeValueSnak( 42 ),
			$this->deserializer->deserialize( [
				'somevalue',
				42,
			] )
		);
	}

	public function testValueSnakDeserialization() {
		$this->assertEquals(
			new PropertyValueSnak( 42, new StringValue( 'foo' ) ),
			$this->deserializer->deserialize( [
				'value',
				42,
				'string',
				'foo',
			] )
		);
	}

	public function testGivenInvalidDataValue_unDerializableValueIsConstructed() {
		$dataValueDeserializer = new DataValueDeserializer( [
			'string' => StringValue::class,
		] );

		$deserializer = new LegacySnakDeserializer( $dataValueDeserializer );

		$snak = $deserializer->deserialize( [
			'value',
			42,
			'string',
			1337,
		] );

		$this->assertInstanceOf( PropertyValueSnak::class, $snak );
		$this->assertSnakHasUnDeseriableValue( $snak );
	}

	private function assertSnakHasUnDeseriableValue( PropertyValueSnak $snak ) {
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
