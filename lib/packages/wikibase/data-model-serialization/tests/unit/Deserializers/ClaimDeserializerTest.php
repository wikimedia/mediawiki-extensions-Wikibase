<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Deserializers\ClaimDeserializer;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

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


		$referencesDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$referencesDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array() ) )
			->will( $this->returnValue( new ReferenceList() ) );

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
			new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) ),
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement'
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

		$claim = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
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

		$claim = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$claim->setRank( Claim::RANK_NORMAL );
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

		$claim = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$claim->setRank( Claim::RANK_DEPRECATED );
		$serializations[] = array(
			$claim,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'deprecated'
			)
		);

		$claim = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
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

		$claim = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
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

		$claim = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
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

	/**
	 * @dataProvider invalidDeserializationProvider
	 */
	public function testInvalidSerialization( $serialization ) {
		$this->setExpectedException( '\Deserializers\Exceptions\DeserializationException' );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function invalidDeserializationProvider() {
		return array(
			array(
				array(
					'type' => 'claim'
				)
			),
			array(
				array(
					'id' => 42,
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
					'rank' => 'nyan-cat'
				)
			),
		);
	}

	public function testQualifiersOrderDeserialization() {
		$snakDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snakDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			) ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );

		$snaksDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
					'P24' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P24'
						)
					),
					'P42' => array(
						array(
							'snaktype' => 'somevalue',
							'property' => 'P42'
						),
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				)
			) )
			->will( $this->returnValue( new SnakList( array(
				new PropertyNoValueSnak( 24 ),
				new PropertySomeValueSnak( 42 ),
				new PropertyNoValueSnak( 42 )
			) ) ) );

		$referencesDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$claimDeserializer = new ClaimDeserializer( $snakDeserializerMock, $snaksDeserializerMock, $referencesDeserializerMock );

		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setQualifiers( new SnakList( array(
			new PropertySomeValueSnak( 42 ),
			new PropertyNoValueSnak( 42 ),
			new PropertyNoValueSnak( 24 )
		) ) );

		$serialization = array(
			'mainsnak' => array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			),
			'qualifiers' => array(
				'P24' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P24'
					)
				),
				'P42' => array(
					array(
						'snaktype' => 'somevalue',
						'property' => 'P42'
					),
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			),
			'qualifiers-order' => array(
				'P42',
				'P24'
			),
			'type' => 'claim'
		);

		$this->assertEquals( $claim->getHash(), $claimDeserializer->deserialize( $serialization )->getHash() );
	}

	public function testQualifiersOrderDeserializationWithTypeError() {
		$serialization = array(
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
			'qualifiers-order' => 'stringInsteadOfArray',
			'type' => 'claim'
		);

		$deserializer = $this->buildDeserializer();

		$this->setExpectedException( 'Deserializers\Exceptions\InvalidAttributeException' );
		$deserializer->deserialize( $serialization );
	}

}
