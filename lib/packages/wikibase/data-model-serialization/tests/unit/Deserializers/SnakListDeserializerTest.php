<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\SnakListDeserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Deserializers\SnakListDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SnakListDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$snakDeserializerMock = $this->getMock( 'Deserializers\Deserializer' );

		$snakDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
					'snaktype' => 'novalue',
					'property' => 'P42'
			) ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );

		return new SnakListDeserializer( $snakDeserializerMock );
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
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				)
			),
			array(
				array(
					'P42' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						),
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				)
			),
			array(
				array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					),
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
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
					'snaktype' => '42value'
				)
			),
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new SnakList(),
				array()
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 )
				) ),
				array(
					'P42' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				)
			),
		);
	}

}
