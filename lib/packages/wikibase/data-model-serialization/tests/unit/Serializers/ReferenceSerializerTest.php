<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\ReferenceSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$snakListSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snakListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SnakList( array() ) ) )
			->will( $this->returnValue( array() ) );

		return new ReferenceSerializer( $snakListSerializerMock );
	}

	public function serializableProvider() {
		return array(
			array(
				new Reference()
			),
			array(
				new Reference( new SnakList( array(
					new PropertyNoValueSnak( 42 )
				) ) )
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
				new SnakList( array() )
			),
		);
	}

	public function serializationProvider() {
		return array(
			array(
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array(),
					'snaks-order' => array()
				),
				new Reference()
			),
		);
	}

	public function testSnaksOrderSerialization() {
		$snakListSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snakListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SnakList( array(
				new PropertyNoValueSnak( new PropertyId( 'P42' ) ),
				new PropertySomeValueSnak( new PropertyId( 'P24' ) ),
				new PropertyNoValueSnak( new PropertyId( 'P24' ) )
			) ) ) )
			->will( $this->returnValue( array() ) );

		$referenceSerializer = new ReferenceSerializer( $snakListSerializerMock );

		$reference = new Reference( new SnakList( array(
			new PropertyNoValueSnak( new PropertyId( 'P42' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P24' ) ),
			new PropertyNoValueSnak( new PropertyId( 'P24' ) )
		) ) );

		$this->assertEquals(
			array(
				'hash' => $reference->getHash(),
				'snaks' => array(),
				'snaks-order' => array(
					'P42',
					'P24'
				)
			),
			$referenceSerializer->serialize( $reference )
		);
	}

}
