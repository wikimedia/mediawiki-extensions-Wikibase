<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\DeserializerFactory
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class ReferenceSerializationRoundtripTest extends TestCase {

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
		return [
			[
				new Reference(),
			],
			[
				new Reference( new SnakList( [
					new PropertyNoValueSnak( 42 ),
				] ) ),
			],
			[
				new Reference( new SnakList( [
					new PropertyNoValueSnak( 42 ),
					new PropertySomeValueSnak( 24 ),
					new PropertyNoValueSnak( 24 ),
				] ) ),
			],
		];
	}

}
