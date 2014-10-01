<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\ClaimSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Serializers\ClaimSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimSerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$snakSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				'snaktype' => 'novalue',
				'property' => "P42"
			) ) );

		$snaksSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snaksSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				'P42' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			) ) );

		$referencesSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$referencesSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array()
				)
			) ) );

		return new ClaimSerializer( $snakSerializerMock, $snaksSerializerMock, $referencesSerializerMock );
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

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setRank( Claim::RANK_DEPRECATED );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'deprecated'
			),
			$claim
		);

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setQualifiers( new SnakList( array() ) );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'type' => 'statement',
				'rank' => 'normal'
			),
			$claim
		);

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setQualifiers( new SnakList( array(
			new PropertyNoValueSnak( 42 )
		) ) );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'qualifiers' => array(
					'P42' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				),
				'qualifiers-order' => array(
					'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			),
			$claim
		);

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setReferences( new ReferenceList( array(
			new Reference()
		) ) );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'references' => array(
					array(
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => array()
					)
				),
				'type' => 'statement',
				'rank' => 'normal'
			),
			$claim
		);

		return $serializations;
	}

	public function testQualifiersOrderSerialization() {
		$snakSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			) ) );

		$snaksSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snaksSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array() ) );

		$referencesSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimSerializer = new ClaimSerializer( $snakSerializerMock, $snaksSerializerMock, $referencesSerializerMock );

		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setQualifiers( new SnakList( array(
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 24 ),
			new PropertyNoValueSnak( 24 )
		) ) );
		$this->assertEquals(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'qualifiers' => array(),
				'qualifiers-order' => array(
					'P42',
					'P24'
				),
				'type' => 'claim'
			),
			$claimSerializer->serialize( $claim )
		);
	}
}
