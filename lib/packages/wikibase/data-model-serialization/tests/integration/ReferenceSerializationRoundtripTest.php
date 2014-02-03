<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Deserializers\ReferenceDeserializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\SnaksDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Serializers\SnaksSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\ReferenceSerializer
 * @covers Wikibase\DataModel\Deserializers\ReferenceDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class ReferenceSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider referenceProvider
	 */
	public function testSnakSerializationRoundtrips( Reference $reference ) {
		$serializer = new ReferenceSerializer( new SnaksSerializer(  new SnakSerializer( new DataValueSerializer() ) ) );
		$deserializer = new ReferenceDeserializer( new SnaksDeserializer( new SnakDeserializer(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		) ) );

		$serialization = $serializer->serialize( $reference );
		$newReference = $deserializer->deserialize( $serialization );
		$this->assertEquals( $reference, $newReference );
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
		);
	}
}
