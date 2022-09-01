<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers DataValues\Serializers\DataValueSerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class StatementListSerializationRoundtripTest extends TestCase {

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
		$newStatements = $deserializerFactory->newStatementListDeserializer()
			->deserialize( $serialization );
		$this->assertEquals( $statements, $newStatements );
	}

	public function snaksProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statement2 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement2->setGuid( 'test2' );

		return [
			[ new StatementList() ],
			[ new StatementList( $statement ) ],
			[ new StatementList( $statement, $statement2 ) ],
		];
	}

}
