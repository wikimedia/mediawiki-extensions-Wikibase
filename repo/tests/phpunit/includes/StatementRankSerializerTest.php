<?php

namespace Wikibase\Repo\Tests;

use DataValues\Serializers\DataValueSerializer;
use Deserializers\Exceptions\DeserializationException;
use Serializers\Exceptions\SerializationException;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\StatementRankSerializer;

/**
 * @covers \Wikibase\Repo\StatementRankSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Thiemo Kreuz
 */
class StatementRankSerializerTest extends \PHPUnit\Framework\TestCase {

	public function rankProvider() {
		return [
			[ Statement::RANK_DEPRECATED, 'deprecated' ],
			[ Statement::RANK_NORMAL, 'normal' ],
			[ Statement::RANK_PREFERRED, 'preferred' ],
		];
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
		$this->expectException( SerializationException::class );
		$serializer->serialize( -1 );
	}

	public function testGivenInvalidSerialization_deserializeFails() {
		$serializer = new StatementRankSerializer();
		$this->expectException( DeserializationException::class );
		$serializer->deserialize( 'invalid' );
	}

}
