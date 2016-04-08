<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\ReferenceListSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\ReferenceListSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceListSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$referenceSerializerFake = $this->getMock( '\Serializers\Serializer' );
		$referenceSerializerFake->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => array()
			) ) );

		return new ReferenceListSerializer( $referenceSerializerFake );
	}

	public function serializableProvider() {
		return array(
			array(
				new ReferenceList()
			),
			array(
				new ReferenceList( array(
					new Reference()
				) )
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
				new Reference()
			),
		);
	}

	public function serializationProvider() {
		return array(
			array(
				array(),
				new ReferenceList()
			),
			array(
				array(
					array(
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => array()
					),
					array(
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => array()
					)
				),
				new ReferenceList( array(
					new Reference( array( new PropertyNoValueSnak( 1 ) ) ),
					new Reference( array( new PropertyNoValueSnak( 1 ) ) )
				) )
			),
		);
	}

}
