<?php

namespace Tests\Wikibase\DataModel\Serializers;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\SnakSerializer
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class SnakSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		return new SnakSerializer( new DataValueSerializer() );
	}

	public function serializableProvider() {
		return array(
			array(
				new PropertyNoValueSnak( 42 )
			),
			array(
				new PropertySomeValueSnak( 42 )
			),
			array(
				new PropertyValueSnak( 42, new StringValue( 'hax' ) )
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
				new ItemId( 'Q42' )
			),
		);
	}

	public function serializationProvider() {
		return array(
			array(
				array(
					'snaktype' => 'novalue',
					'property' => 'P42',
					'hash' => '5c33520fbfb522444868b4168a35d4b919370018'
				),
				new PropertyNoValueSnak( 42 )
			),
			array(
				array(
					'snaktype' => 'somevalue',
					'property' => 'P42',
					'hash' => '1c5c4a30999292cd6592a7a6530322d095fc62d4'
				),
				new PropertySomeValueSnak( 42 )
			),
			array(
				array(
					'snaktype' => 'value',
					'property' => 'P42',
					'hash' => 'f39228cb4e94174c87e966c32b02ad93b3512fce',
					'datavalue' => array(
						'value' => 'hax',
						'type' => 'string',
					)
				),
				new PropertyValueSnak( 42, new StringValue( 'hax' ) )
			),
		);
	}

	public function testSnakSerializationWithoutHash() {
		$serializer = new SnakSerializer( new DataValueSerializer(), false );

		$snak = new PropertyValueSnak( 42, new StringValue( 'hax' ) );
		$serialization = $serializer->serialize( $snak );

		$this->assertArrayNotHasKey( 'hash', $serialization );
	}

}
