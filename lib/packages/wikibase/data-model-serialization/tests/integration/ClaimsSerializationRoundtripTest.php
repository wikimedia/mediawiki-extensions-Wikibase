<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Claim\Claim;
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
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );

		$claim2 = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim2->setGuid( 'test2' );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'teststatement' );

		return array(
			array(
				new Claims()
			),
			array(
				new Claims( array(
					$claim
				) )
			),
			array(
				new Claims( array(
					$claim,
					$claim2
				) )
			),
			array(
				new Claims( array(
					$claim,
					$statement
				) )
			),
		);
	}
}
