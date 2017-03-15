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
		$serializeWithHash = false;
		return new SnakSerializer( new DataValueSerializer(), $serializeWithHash );
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
				),
				new PropertyNoValueSnak( 42 )
			),
			array(
				array(
					'snaktype' => 'somevalue',
					'property' => 'P42',
				),
				new PropertySomeValueSnak( 42 )
			),
			array(
				array(
					'snaktype' => 'value',
					'property' => 'P42',
					'datavalue' => array(
						'value' => 'hax',
						'type' => 'string',
					)
				),
				new PropertyValueSnak( 42, new StringValue( 'hax' ) )
			),
		);
	}

	public function testSnakSerializationWithHash() {
		$serializer = new SnakSerializer( new DataValueSerializer() );

		$snak = new PropertyValueSnak( 42, new StringValue( 'hax' ) );
		$serialization = $serializer->serialize( $snak );

		$this->assertArrayHasKey( 'hash', $serialization );
		$this->assertInternalType( 'string', $serialization['hash'] );
		$this->assertNotEmpty( $serialization['hash'] );
	}

}
