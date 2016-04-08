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
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class ReferenceDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
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
			'hash' => '20726a1e99eab73834c0f4a25f3c5c2561993e6e',
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

	/**
	 * @dataProvider invalidDeserializationProvider
	 */
	public function testInvalidSerialization( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function invalidDeserializationProvider() {
		return array(
			array(
				'hash' => 'da',
				'snaks' => array()
			),
		);
	}

	public function testGivenInvalidSnaksOrderAttribute_exceptionIsThrown() {
		$this->setExpectedException( 'Deserializers\Exceptions\InvalidAttributeException' );
		$this->buildDeserializer()->deserialize( array(
			'hash' => 'foo',
			'snaks' => array(),
			'snaks-order' => null
		) );
	}

}
