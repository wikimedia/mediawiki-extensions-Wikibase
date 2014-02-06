<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Deserializers\ReferenceDeserializer;
use Wikibase\DataModel\Deserializers\ReferencesDeserializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\SnaksDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\References;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Serializers\ReferencesSerializer;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Serializers\SnaksSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\ReferencesSerializer
 * @covers Wikibase\DataModel\Deserializers\ReferencesDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class ReferencesSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider referencesProvider
	 */
	public function testReferenceSerializationRoundtrips( References $reference ) {
		$serializer = new ReferencesSerializer( new ReferenceSerializer(
			new SnaksSerializer( new SnakSerializer( new DataValueSerializer() ) )
		) );
		$deserializer = new ReferencesDeserializer( new ReferenceDeserializer( new SnaksDeserializer( new SnakDeserializer(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		) ) ) );

		$serialization = $serializer->serialize( $reference );
		$newReferences = $deserializer->deserialize( $serialization );
		$this->assertEquals( $reference, $newReferences );
	}

	public function referencesProvider() {
		return array(
			array(
				new ReferenceList( array() )
			),
			array(
				new ReferenceList( array(
					new Reference()
				) )
			),
			array(
				new ReferenceList( array(
					new Reference( new SnakList( array(
						new PropertyNoValueSnak( 42 )
					) ) ),
					new Reference( new SnakList( array(
						new PropertyNoValueSnak( 43 )
					) ) )
				) )
			),
		);
	}
}
