<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class SnakListSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider snakListProvider
	 */
	public function testSnakSerializationRoundtrips( SnakList $snaks ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newSnakListSerializer()->serialize( $snaks );
		$newSnaks = $deserializerFactory->newSnakListDeserializer()->deserialize( $serialization );
		$this->assertEquals( $snaks, $newSnaks );
	}

	public function snakListProvider() {
		return array(
			array(
				new SnakList( array() )
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 )
				) )
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 ),
					new PropertyNoValueSnak( 43 )
				) )
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 ),
					new PropertySomeValueSnak( 42 ),
					new PropertyNoValueSnak( 43 ),
				) )
			),
		);
	}

}
