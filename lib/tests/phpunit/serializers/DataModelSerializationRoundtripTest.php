<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DataModelSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityProvider
	 */
	public function testRoundtrip( Entity $expectedEntity ) {
		$legacySerializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$entityType = $expectedEntity->getType();
		$options = new SerializationOptions();
		$legacySerializer = $legacySerializerFactory->newSerializerForEntity( $entityType, $options );
		$legacyUnserializer = $legacySerializerFactory->newUnserializerForEntity( $entityType, $options );

		// XXX: What's the point of requiring this in the constructor?
		$dataValueSerializer = new DataValueSerializer();
		$serializerFactory = new \Wikibase\DataModel\SerializerFactory( $dataValueSerializer );
		$serializer = $serializerFactory->newEntitySerializer();

		// XXX: What's the point of requiring this in the constructor?
		$dataValueDeserializer = new DataValueDeserializer();
		// XXX: What's the point of requiring this in the constructor?
		$entityIdParser = new BasicEntityIdParser();
		$deserializerFactory = new \Wikibase\DataModel\DeserializerFactory( $dataValueDeserializer, $entityIdParser );
		$deserializer = $deserializerFactory->newEntityDeserializer();

		// Old encoder -> new decoder -> new encoder -> old decoder.
		$serialization = $legacySerializer->getSerialized( $expectedEntity );
		$entity = $deserializer->deserialize( $serialization );
		$serialization = $serializer->serialize( $entity );
		$actualEntity = $legacyUnserializer->newFromSerialization( $serialization );

		// XXX: Does it make sense to compare in both directions? It can't hurt, can't it?
		$this->assertTrue( $actualEntity->equals( $expectedEntity ) );
		$this->assertTrue( $expectedEntity->equals( $actualEntity ) );

		// New encoder -> old decoder -> old encoder -> new decoder.
		$serialization = $serializer->serialize( $expectedEntity );
		$entity = $legacyUnserializer->newFromSerialization( $serialization );
		$serialization = $legacySerializer->getSerialized( $entity );
		$actualEntity = $deserializer->deserialize( $serialization );

		// XXX: Does it make sense to compare in both directions? It can't hurt, can't it?
		$this->assertTrue( $actualEntity->equals( $expectedEntity ) );
		$this->assertTrue( $expectedEntity->equals( $actualEntity ) );
	}

	public function entityProvider() {
		$tests = array();

		$property = Property::newFromType( 'string' );
		$property->setId( new PropertyId( 'P1' ) );
		$tests[] = array( $property );

		return $tests;
	}

}
