<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class SnakSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider snakProvider
	 */
	public function testSnakSerializationRoundtrips( Snak $snak ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer( array (
				'string' => 'DataValues\StringValue',
			) ),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newSnakSerializer()->serialize( $snak );
		$newSnak = $deserializerFactory->newSnakDeserializer()->deserialize( $serialization );
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
}
