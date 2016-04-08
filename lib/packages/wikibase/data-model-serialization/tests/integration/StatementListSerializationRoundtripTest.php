<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class StatementListSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider snaksProvider
	 */
	public function testSnakSerializationRoundtrips( StatementList $statements ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newStatementListSerializer()->serialize( $statements );
		$newStatements = $deserializerFactory->newStatementListDeserializer()->deserialize( $serialization );
		$this->assertEquals( $statements, $newStatements );
	}

	public function snaksProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statement2 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement2->setGuid( 'test2' );

		return array(
			array(
				new StatementList()
			),
			array(
				new StatementList( array(
					$statement
				) )
			),
			array(
				new StatementList( array(
					$statement,
					$statement2
				) )
			),
		);
	}

}
