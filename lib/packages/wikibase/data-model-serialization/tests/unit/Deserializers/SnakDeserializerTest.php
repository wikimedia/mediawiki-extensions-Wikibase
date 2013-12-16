<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use Tests\Wikibase\DataModel\Unserializers\DeserializerBaseTest;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Deserializers\SnakDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SnakDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		return new SnakDeserializer(
			new DataValueDeserializer( array (
				'string' => 'DataValues\StringValue',
			) ),
			new BasicEntityIdParser()
		);
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				)
			),
			array(
				array(
					'snaktype' => 'somevalue',
					'property' => 'P42'
				)
			),
			array(
				array(
					'snaktype' => 'value',
					'property' => 'P42',
					'datavalue' => array(
						'type' => 'string',
						'value' => 'hax'
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
				array()
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
				new PropertyNoValueSnak( 42 ),
				array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				)
			),
			array(
				new PropertySomeValueSnak( 42 ),
				array(
					'snaktype' => 'somevalue',
					'property' => 'P42'
				)
			),
			array(
				new PropertyValueSnak( 42, new StringValue( 'hax' ) ),
				array(
					'snaktype' => 'value',
					'property' => 'P42',
					'datavalue' => array(
						'type' => 'string',
						'value' => 'hax'
					)
				)
			),
		);
	}
}