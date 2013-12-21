<?php

namespace Tests\Wikibase\DataModel\Deserializers\Snak;

use Tests\Wikibase\DataModel\Unserializers\DeserializerBaseTest;
use Wikibase\DataModel\Deserializers\Snak\PropertyNoValueSnakDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Deserializers\PropertyNoValueSnakDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyNoValueSnakDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		return new PropertyNoValueSnakDeserializer(
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
					'snaktype' => 'somevalue'
				)
			),
			array(
				array(
					'snaktype' => 'somevalue',
					'property' => 'P42'
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
		);
	}
}
