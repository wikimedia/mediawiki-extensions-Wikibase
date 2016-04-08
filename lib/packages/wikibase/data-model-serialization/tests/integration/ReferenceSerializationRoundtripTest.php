<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class ReferenceSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider referenceProvider
	 */
	public function testSnakSerializationRoundtrips( Reference $reference ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newReferenceSerializer()->serialize( $reference );
		$newReference = $deserializerFactory->newReferenceDeserializer()->deserialize( $serialization );
		$this->assertTrue( $reference->equals( $newReference ) );
	}

	public function referenceProvider() {
		return array(
			array(
				new Reference()
			),
			array(
				new Reference( new SnakList( array(
					new PropertyNoValueSnak( 42 )
				) ) )
			),
			array(
				new Reference( new SnakList( array(
					new PropertyNoValueSnak( 42 ),
					new PropertySomeValueSnak( 24 ),
					new PropertyNoValueSnak( 24 )
				) ) )
			),
		);
	}

}
