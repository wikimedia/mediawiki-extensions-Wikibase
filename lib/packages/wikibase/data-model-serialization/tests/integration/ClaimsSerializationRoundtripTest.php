<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimsSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider snaksProvider
	 */
	public function testSnakSerializationRoundtrips( Claims $claims ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newClaimsSerializer()->serialize( $claims );
		$newClaims = $deserializerFactory->newClaimsDeserializer()->deserialize( $serialization );
		$this->assertEquals( $claims, $newClaims );
	}

	public function snaksProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statement2 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement2->setGuid( 'test2' );

		return array(
			array(
				new Claims()
			),
			array(
				new Claims( array(
					$statement
				) )
			),
			array(
				new Claims( array(
					$statement,
					$statement2
				) )
			),
		);
	}

}
