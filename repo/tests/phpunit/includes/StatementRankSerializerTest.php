<?php

namespace Wikibase\Test;

use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Entity\PropertyId;
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
 * @licence GNU GPL v2+
 * @author Addshore
 */
class StatementRankSerializerTest extends \PHPUnit_Framework_TestCase {

	public function rankProvider() {
		$ranks = array(
			Statement::RANK_NORMAL,
			Statement::RANK_PREFERRED,
			Statement::RANK_DEPRECATED,
		);

		return $this->arrayWrap( $ranks );
	}

	/**
	 * @dataProvider rankProvider
	 */
	public function testRankSerialization( $rank ) {
		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( 'P42' ) ) );
		$statement->setRank( $rank );

		$factory = new SerializerFactory( new DataValueSerializer() );
		$statementSerializer = $factory->newStatementSerializer();

		$serialization = $statementSerializer->serialize( $statement );

		$rankSerializer = new StatementRankSerializer();

		$this->assertEquals(
			$rank,
			$rankSerializer->deserialize( $serialization['rank'] ),
			'Roundtrip between rank serialization and unserialization 1'
		);

		$this->assertEquals(
			$serialization['rank'],
			$rankSerializer->serialize( $rank ),
			'Roundtrip between rank serialization and unserialization 2'
		);
	}

	protected function arrayWrap( array $elements ) {
		return array_map(
			function ( $element ) {
				return array( $element );
			},
			$elements
		);
	}

}
