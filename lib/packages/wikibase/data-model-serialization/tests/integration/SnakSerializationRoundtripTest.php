<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers DataValues\Deserializers\DataValueDeserializer
 * @covers DataValues\UnDeserializableValue
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 * @author Thiemo Kreuz
 */
class SnakSerializationRoundtripTest extends TestCase {

	private function getSnakSerializer() {
		$factory = new SerializerFactory( new DataValueSerializer() );
		return $factory->newSnakSerializer();
	}

	private function getSnakDeserializer( array $dataValueClasses = [] ) {
		$factory = new DeserializerFactory(
			new DataValueDeserializer( $dataValueClasses ),
			new BasicEntityIdParser()
		);
		return $factory->newSnakDeserializer();
	}

	/**
	 * @dataProvider snakProvider
	 */
	public function testSnakSerializationRoundtrips( Snak $snak ) {
		$serializer = $this->getSnakSerializer();
		$deserializer = $this->getSnakDeserializer( [
			'string' => StringValue::class,
		] );

		$serialization = $serializer->serialize( $snak );
		$newSnak = $deserializer->deserialize( $serialization );

		$this->assertEquals( $snak, $newSnak );
	}

	public function snakProvider() {
		return [
			[
				new PropertyNoValueSnak( 42 ),
			],
			[
				new PropertySomeValueSnak( 42 ),
			],
			[
				new PropertyValueSnak( 42, new StringValue( 'hax' ) ),
			],
		];
	}

	public function testUnDeserializableValueToStringValueRoundtrip() {
		$serializer = $this->getSnakSerializer();
		$deserializer = $this->getSnakDeserializer( [
			'string' => StringValue::class,
		] );

		$badSnak = new PropertyValueSnak( 42, new UnDeserializableValue( 'Yay', 'string', '' ) );
		$serialization = $serializer->serialize( $badSnak );
		$newSnak = $deserializer->deserialize( $serialization );

		$goodSnak = new PropertyValueSnak( 42, new StringValue( 'Yay' ) );
		$this->assertEquals( $goodSnak, $newSnak );
	}

	public function testStringValueToUnDeserializableValueRoundtrip() {
		$serializer = $this->getSnakSerializer();
		$deserializer = $this->getSnakDeserializer();

		$goodSnak = new PropertyValueSnak( 42, new StringValue( 'Yay' ) );
		$serialization = $serializer->serialize( $goodSnak );
		$newSnak = $deserializer->deserialize( $serialization );

		/** @var PropertyValueSnak $newSnak */
		$this->assertInstanceOf( PropertyValueSnak::class, $newSnak );
		$this->assertSame( 'P42', $newSnak->getPropertyId()->getSerialization() );

		/** @var UnDeserializableValue $newValue */
		$newValue = $newSnak->getDataValue();
		$this->assertInstanceOf( UnDeserializableValue::class, $newValue );
		$this->assertSame( 'Yay', $newValue->getValue() );
		$this->assertSame( 'string', $newValue->getTargetType() );
	}

}
