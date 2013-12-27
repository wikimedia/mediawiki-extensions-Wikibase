<?php

namespace Tests\Wikibase\DataModel\Serializers;

use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\ClaimSerializer;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\ClaimSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimSerializerTest extends SerializerBaseTest {

	public function buildSerializer() {
		return new ClaimSerializer( new SnakSerializer( new DataValueSerializer() ) );
	}

	public function serializableProvider() {
		return array(
			array(
				new Claim( new PropertyNoValueSnak( 42 ) )
			),
			array(
				new Statement( new PropertyNoValueSnak( 42 ) )
			),
		);
	}

	public function nonSerializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				new ItemId( 'Q42' )
			),
		);
	}

	public function serializationProvider() {
		$serializations = array();

		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'claim'
			),
			new Claim( new PropertyNoValueSnak( 42 ) )
		);

		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			),
			new Statement( new PropertyNoValueSnak( 42 ) )
		);

		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'q42' );
		$serializations[] = array(
			array(
				'id' => 'q42',
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'type' => 'claim'
			),
			$claim
		);

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setRank( Claim::RANK_PREFERRED );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'type' => 'statement',
				'rank' => 'preferred'
			),
			$claim
		);

		return $serializations;
	}
}
