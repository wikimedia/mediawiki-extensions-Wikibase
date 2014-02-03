<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\ReferenceSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceSerializerTest extends SerializerBaseTest {

	public function buildSerializer() {
		$referenceSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$referenceSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SnakList( array() ) ) )
			->will( $this->returnValue( array() ) );

		return new ReferenceSerializer( $referenceSerializerMock );
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
					'snaks' => array()
				),
				new Reference()
			),
		);
	}
}
