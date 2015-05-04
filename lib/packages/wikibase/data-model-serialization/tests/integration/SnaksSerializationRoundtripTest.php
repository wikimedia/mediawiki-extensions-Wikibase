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
use Wikibase\DataModel\Snak\Snaks;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class SnaksSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider snaksProvider
	 */
	public function testSnakSerializationRoundtrips( Snaks $snak ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newSnaksSerializer()->serialize( $snak );
		$newSnaks = $deserializerFactory->newSnaksDeserializer()->deserialize( $serialization );
		$this->assertEquals( $snak, $newSnaks );
	}

	public function snaksProvider() {
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
