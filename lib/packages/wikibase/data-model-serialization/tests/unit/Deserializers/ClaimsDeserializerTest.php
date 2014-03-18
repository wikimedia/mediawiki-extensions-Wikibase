<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Deserializers\ClaimsDeserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Deserializers\ClaimsDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimsDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );

		$claimDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$claimDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			) ) )
			->will( $this->returnValue( $claim ) );

		return new ClaimsDeserializer( $claimDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array()
			),
			array(
				array(
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
			array(
				array(
					'P42' => array(
						array(
							'mainsnak' => array(
								'snaktype' => 'novalue',
								'property' => 'P42'
							),
							'type' => 'statement',
							'rank' => 'normal'
						),
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
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				42
			),
			array(
				array(
					'id' => 'P10'
				)
			),
			array(
				array(
					'type' => '42'
				)
			),
		);
	}

	public function deserializationProvider() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );

		return array(
			array(
				new Claims(),
				array()
			),
			array(
				new Claims( array(
					$claim
				) ),
				array(
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
		);
	}
}
