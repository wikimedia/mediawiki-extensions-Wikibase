<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\PropertySerializer;
use Wikibase\DataModel\Serializers\FingerprintSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\PropertySerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertySerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$claimsSerializerMock = $this->getMock( 'Serializers\Serializer' );
		$claimsSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( function( Claims $claims ) {
				if ( $claims->isEmpty() ) {
					return array();
				}

				return array(
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
				);
			} ) );

		$fingerprintSerializer = new FingerprintSerializer( false );

		return new PropertySerializer( $fingerprintSerializer, $claimsSerializerMock );
	}

	public function serializableProvider() {
		return array(
			array(
				Property::newFromType( 'string' )
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
		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );

		$provider = array(
			array(
				array(
					'type' => 'property',
					'datatype' => 'string',
					'labels' => array(),
					'descriptions' => array(),
					'aliases' => array(),
					'claims' => array(),
				),
				$property
			),
		);

		$property = Property::newEmpty();
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			array(
				'type' => 'property',
				'datatype' => '',
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
				),
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
			),
			$property
		);

		return $provider;
	}

}
