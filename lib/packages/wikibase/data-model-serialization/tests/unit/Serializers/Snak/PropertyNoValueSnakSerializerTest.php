<?php

namespace Tests\Wikibase\DataModel\Serializers\Snak;

use DataValues\StringValue;
use Tests\Wikibase\DataModel\Serializers\SerializerBaseTest;
use Wikibase\DataModel\Serializers\Snak\PropertyNoValueSnakSerializer;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\Snak\PropertyNoValueSnakSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyNoValueSnakSerializerTest extends SerializerBaseTest {

	public function buildSerializer() {
		return new PropertyNoValueSnakSerializer();
	}

	public function serializableProvider() {
		return array(
			array(
				new PropertyNoValueSnak( 42 )
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
				new PropertySomeValueSnak( 42 )
			),
			array(
				new PropertyValueSnak( 42, new StringValue( 'test' ) )
			)
		);
	}

	public function serializationProvider() {
		return array(
			array(
				array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				new PropertyNoValueSnak( 42 )
			),
		);
	}
}
