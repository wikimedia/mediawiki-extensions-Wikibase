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
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class StatementSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider snaksProvider
	 */
	public function testSnakSerializationRoundtrips( Statement $statement ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newStatementSerializer()->serialize( $statement );
		$newStatement = $deserializerFactory->newStatementDeserializer()->deserialize( $serialization );
		$this->assertEquals( $statement->getHash(), $newStatement->getHash() );
	}

	public function snaksProvider() {
		$statements = array();

		$statements[] = array(
			new Statement( new PropertyNoValueSnak( 42 ) )
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'q42' );
		$statements[] = array( $statement );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_PREFERRED );
		$statements[] = array( $statement );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_DEPRECATED );
		$statements[] = array( $statement );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( array() ) );
		$statements[] = array( $statement );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( array(
			new PropertySomeValueSnak( 42 ),
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 24 )
		) ) );
		$statements[] = array( $statement );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setReferences( new ReferenceList( array(
			new Reference( array(
				new PropertySomeValueSnak( 42 ),
				new PropertyNoValueSnak( 42 ),
				new PropertySomeValueSnak( 24 )
			) )
		) ) );
		$statements[] = array( $statement );

		return $statements;
	}

}
