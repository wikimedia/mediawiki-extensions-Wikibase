<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Serializers\SnaksSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\SnaksSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SnaksSerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$snakSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new PropertyNoValueSnak( 42 ) ) )
			->will( $this->returnValue( array(
				'snaktype' => 'novalue',
				'property' => "P42"
			) ) );

		return new SnaksSerializer( $snakSerializerMock );
	}

	public function serializableProvider() {
		return array(
			array(
				new SnakList()
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 )
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
				new PropertyNoValueSnak( 42 )
			),
		);
	}

	public function serializationProvider() {
		return array(
			array(
				array(),
				new SnakList()
			),
			array(
				array(
					'P42' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				),
				new SnakList( array(
					new PropertyNoValueSnak( 42 )
				) )
			),
		);
	}
}
