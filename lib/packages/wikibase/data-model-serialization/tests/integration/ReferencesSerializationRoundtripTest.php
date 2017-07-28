<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class ReferencesSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider referencesProvider
	 */
	public function testReferenceSerializationRoundtrips( ReferenceList $references ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newReferencesSerializer()->serialize( $references );
		$newReferences = $deserializerFactory->newReferencesDeserializer()->deserialize( $serialization );
		$this->assertReferenceListEquals( $references, $newReferences );
	}

	public function referencesProvider() {
		return [
			[
				new ReferenceList( [] )
			],
			[
				new ReferenceList( [
					new Reference()
				] )
			],
			[
				new ReferenceList( [
					new Reference( new SnakList( [
						new PropertyNoValueSnak( 42 )
					] ) ),
					new Reference( new SnakList( [
						new PropertyNoValueSnak( 43 )
					] ) )
				] )
			],
		];
	}

	/**
	 * @param ReferenceList $expected
	 * @param ReferenceList $actual
	 */
	public function assertReferenceListEquals( ReferenceList $expected, ReferenceList $actual ) {
		$this->assertTrue( $actual->equals( $expected ), 'The two ReferenceList are different' );
	}

}
