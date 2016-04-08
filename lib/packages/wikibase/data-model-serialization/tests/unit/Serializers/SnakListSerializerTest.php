<?php

namespace Tests\Wikibase\DataModel\Serializers;

use stdClass;
use Wikibase\DataModel\Serializers\SnakListSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\SnakListSerializer
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class SnakListSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$snakSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new PropertyNoValueSnak( 42 ) ) )
			->will( $this->returnValue( array(
				'snaktype' => 'novalue',
				'property' => "P42"
			) ) );

		return new SnakListSerializer( $snakSerializerMock, false );
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

	public function testSnakListSerializerWithOptionObjectsForMaps() {
		$snakSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new PropertyNoValueSnak( 42 ) ) )
			->will( $this->returnValue( array(
				'snaktype' => 'novalue',
				'property' => "P42"
			) ) );
		$serializer = new SnakListSerializer( $snakSerializerMock, true );

		$snaklist = new SnakList( array( new PropertyNoValueSnak( 42 ) ) );

		$serial = new stdClass();
		$serial->P42 = array( array(
			'snaktype' => 'novalue',
			'property' => 'P42',
	 ) );
		$this->assertEquals( $serial, $serializer->serialize( $snaklist ) );
	}

}
