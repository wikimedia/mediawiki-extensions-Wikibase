<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\ReferenceDeserializer;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Deserializers\ReferenceDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$snaksDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array() ) )
			->will( $this->returnValue( new SnakList() ) );

		return new ReferenceDeserializer( $snaksDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array()
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
				array()
			),
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new Reference(),
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array()
				)
			),
			array(
				new Reference(),
				array(
					'snaks' => array()
				)
			),
		);
	}

	public function testSnaksOrderDeserialization() {
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
				new PropertyNoValueSnak( new PropertyId( 'P24' ) ),
				new PropertySomeValueSnak( new PropertyId( 'P42' ) ),
				new PropertyNoValueSnak( new PropertyId( 'P42' ) )
			) ) ) );

		$referenceDeserializer = new ReferenceDeserializer( $snaksDeserializerMock );

		$reference = new Reference( new SnakList( array(
			new PropertySomeValueSnak( new PropertyId( 'P42' ) ),
			new PropertyNoValueSnak( new PropertyId( 'P42' ) ),
			new PropertyNoValueSnak( new PropertyId( 'P24' ) )
		) ) );

		$serialization = array(
			'hash' => 'c473c0006ec3e5930a0b3d87406909d4c87dae96',
			'snaks' => array(
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
			'snaks-order' => array(
				'P42',
				'P24'
			)
		);

		$this->assertTrue( $reference->equals( $referenceDeserializer->deserialize( $serialization ) ) );
	}
}