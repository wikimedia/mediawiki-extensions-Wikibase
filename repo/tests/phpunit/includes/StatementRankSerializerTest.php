<?php

namespace Wikibase\Test;

use DataValues\Serializers\DataValueSerializer;
use Deserializers\Exceptions\DeserializationException;
use PHPUnit_Framework_TestCase;
use Serializers\Exceptions\SerializationException;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\StatementRankSerializer;

/**
 * @covers Wikibase\StatementRankSerializer
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Thiemo Mättig
 */
class StatementRankSerializerTest extends PHPUnit_Framework_TestCase {

	public function rankProvider() {
		return array(
			array( Statement::RANK_DEPRECATED, 'deprecated' ),
			array( Statement::RANK_NORMAL, 'normal' ),
			array( Statement::RANK_PREFERRED, 'preferred' ),
		);
	}

	/**
	 * @dataProvider rankProvider
	 */
	public function testSerialize( $rank, $expected ) {
		$serializer = new StatementRankSerializer();
		$serialization = $serializer->serialize( $rank );
		$this->assertSame( $expected, $serialization );
	}

	/**
	 * @dataProvider rankProvider
	 */
	public function testDeserialize( $expected, $serialization ) {
		$serializer = new StatementRankSerializer();
		$deserialization = $serializer->deserialize( $serialization );
		$this->assertSame( $expected, $deserialization );
	}

	/**
	 * @dataProvider rankProvider
	 */
	public function testSerializerFactoryRoundtrip( $rank ) {
		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement->setRank( $rank );

		$factory = new SerializerFactory( new DataValueSerializer() );
		$statementSerializer = $factory->newStatementSerializer();

		$serialization = $statementSerializer->serialize( $statement );

		$rankSerializer = new StatementRankSerializer();

		$this->assertSame(
			$rank,
			$rankSerializer->deserialize( $serialization['rank'] ),
			'reference serialization can be deserialized'
		);

		$this->assertSame(
			$serialization['rank'],
			$rankSerializer->serialize( $rank ),
			'serialization is identical to reference'
		);
	}

	public function testGivenInvalidRank_serializationFails() {
		$serializer = new StatementRankSerializer();
		$this->setExpectedException( SerializationException::class );
		$serializer->serialize( -1 );
	}

	public function testGivenInvalidSerialization_deserializeFails() {
		$serializer = new StatementRankSerializer();
		$this->setExpectedException( DeserializationException::class );
		$serializer->deserialize( 'invalid' );
	}

}
