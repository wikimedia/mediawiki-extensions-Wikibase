<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\PropertySerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\PropertySerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertySerializerTest extends SerializerBaseTest {

	public function buildSerializer() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );

		$claimsSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimsSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new Claims( array( $claim ) ) ) )
			->will( $this->returnValue( array(
				'P42' => array(
					array(
						'mainsnak' => array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						),
						'type' => 'statement',
						'rank' => 'normal'
					)
				)
			) ) );

		return new PropertySerializer( $claimsSerializerMock );
	}

	public function serializableProvider() {
		return array(
			array(
				Property::newEmpty()
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
				Item::newEmpty()
			),
		);
	}

	public function serializationProvider() {
		$provider = array();

		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		$provider[] = array(
			array(
				'type' => 'property',
				'datatype' => 'string'
			),
			$property
		);

		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		$property->setId( new PropertyId( 'P42' ) );
		$provider[] = array(
			array(
				'type' => 'property',
				'datatype' => 'string',
				'id' => 'P42'
			),
			$property
		);

		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );
		$property->setClaims( new Claims( array( $claim ) ) );
		$provider[] = array(
			array(
				'type' => 'property',
				'datatype' => 'string',
				'claims' => array(
					'P42' => array(
						array(
							'mainsnak' => array(
								'snaktype' => 'novalue',
								'property' => 'P42'
							),
							'type' => 'statement',
							'rank' => 'normal'
						)
					)
				)
			),
			$property
		);

		return $provider;
	}
}
