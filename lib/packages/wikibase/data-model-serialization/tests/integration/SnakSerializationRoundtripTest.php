<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 * @author Thiemo Mättig
 */
class SnakSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	private function getSnakSerializer() {
		$factory = new SerializerFactory( new DataValueSerializer() );
		return $factory->newSnakSerializer();
	}

	private function getSnakDeserializer( array $dataValueClasses = array() ) {
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
		$deserializer = $this->getSnakDeserializer( array (
			'string' => 'DataValues\StringValue',
		) );

		$serialization = $serializer->serialize( $snak );
		$newSnak = $deserializer->deserialize( $serialization );

		$this->assertEquals( $snak, $newSnak );
	}

	public function snakProvider() {
		return array(
			array(
				new PropertyNoValueSnak( 42 ),
			),
			array(
				new PropertySomeValueSnak( 42 ),
			),
			array(
				new PropertyValueSnak( 42, new StringValue( 'hax' ) ),
			),
		);
	}

	public function testUnDeserializableValueToStringValueRoundtrip() {
		$serializer = $this->getSnakSerializer();
		$deserializer = $this->getSnakDeserializer( array (
			'string' => 'DataValues\StringValue',
		) );

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

		$badSnak = new PropertyValueSnak( 42, new UnDeserializableValue( 'Yay', 'string', '' ) );
		$this->assertEquals( $badSnak, $newSnak );
	}

}
