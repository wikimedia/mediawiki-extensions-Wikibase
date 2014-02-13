<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Deserializers\ClaimDeserializer;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Deserializers\ClaimDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$snakDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snakDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
					'snaktype' => 'novalue',
					'property' => 'P42'
			) ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );
		$snakDeserializerMock->expects( $this->any() )
			->method( 'isDeserializerFor' )
			->with( $this->equalTo( array(
					'snaktype' => 'novalue',
					'property' => 'P42'
			) ) )
			->will( $this->returnValue( true ) );

		$snaksDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'P42' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			) ) )
			->will( $this->returnValue( new SnakList( array(
				new PropertyNoValueSnak( 42 )
			) ) ) );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'isDeserializerFor' )
			->with( $this->equalTo( array(
				'P42' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			) ) )
			->will( $this->returnValue( true ) );


		$referencesDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$referencesDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array() ) )
			->will( $this->returnValue( new ReferenceList() ) );
		$referencesDeserializerMock->expects( $this->any() )
			->method( 'isDeserializerFor' )
			->with( $this->equalTo( array() ) )
			->will( $this->returnValue( true ) );

		return new ClaimDeserializer( $snakDeserializerMock, $snaksDeserializerMock, $referencesDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'mainsnak' => array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					),
					'type' => 'claim'
				)
			),
			array(
				array(
					'mainsnak' => array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					),
					'type' => 'statement',
					'rank' => 'normal'
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
		$serializations = array();

		$serializations[] = array(
			new Claim( new PropertyNoValueSnak( 42 ) ),
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'claim'
			)
		);

		$serializations[] = array(
			new Statement( new PropertyNoValueSnak( 42 ) ),
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			)
		);

		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'q42' );
		$serializations[] = array(
			$claim,
			array(
				'id' => 'q42',
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'claim'
			)
		);

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setRank( Claim::RANK_PREFERRED );
		$serializations[] = array(
			$claim,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'preferred'
			)
		);

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setQualifiers( new SnakList( array() ) );
		$serializations[] = array(
			$claim,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			)
		);

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setQualifiers( new SnakList( array(
			new PropertyNoValueSnak( 42 )
		) ) );
		$serializations[] = array(
			$claim,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'qualifiers' => array(
					'P42' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				),
				'type' => 'statement',
				'rank' => 'normal'
			)
		);

		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setReferences( new ReferenceList() );
		$serializations[] = array(
			$claim,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'references' => array(),
				'type' => 'statement',
				'rank' => 'normal'
			)
		);

		return $serializations;
	}
}
