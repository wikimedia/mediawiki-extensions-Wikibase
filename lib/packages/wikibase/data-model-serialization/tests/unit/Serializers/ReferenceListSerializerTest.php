<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\ReferenceListSerializer;

/**
 * @covers Wikibase\DataModel\Serializers\ReferenceListSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceListSerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$referenceSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$referenceSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new Reference() ) )
			->will( $this->returnValue( array(
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => array()
			) ) );

		return new ReferenceListSerializer( $referenceSerializerMock );
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
					)
				),
				new ReferenceList( array(
					new Reference()
				) )
			),
		);
	}
}
