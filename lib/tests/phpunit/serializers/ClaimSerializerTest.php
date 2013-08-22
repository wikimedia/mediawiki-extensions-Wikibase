<?php

namespace Wikibase\Test;

use Wikibase\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ClaimSerializer
 *
 * @file
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ClaimSerializer';
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$id = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 );

		$validArgs[] = new \Wikibase\Claim( new \Wikibase\PropertyNoValueSnak( $id ) );

		$validArgs[] = new \Wikibase\Claim( new \Wikibase\PropertySomeValueSnak( $id ) );

		$validArgs = $this->arrayWrap( $validArgs );

		$claim = new \Wikibase\Claim( new \Wikibase\PropertyNoValueSnak( $id ) );

		$validArgs[] = array(
			$claim,
			array(
				'id' => $claim->getGuid(),
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42',
				),
				'type' => 'claim',
			),
		);

		$statement = new \Wikibase\Statement( new \Wikibase\PropertyNoValueSnak( $id ) );

		$validArgs[] = array(
			$statement,
			array(
				'id' => $statement->getGuid(),
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42',
				),
				'rank' => 'normal',
				'type' => 'statement',
			),
		);

		return $validArgs;
	}

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
		$id = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 );
		$statement = new \Wikibase\Statement( new \Wikibase\PropertyNoValueSnak( $id ) );

		$statement->setRank( $rank );

		$serializer = new ClaimSerializer();

		$serialization = $serializer->getSerialized( $statement );

		$this->assertEquals(
			$rank,
			ClaimSerializer::unserializeRank( $serialization['rank'] ),
			'Roundtrip between rank serialization and unserialization'
		);
	}

}
