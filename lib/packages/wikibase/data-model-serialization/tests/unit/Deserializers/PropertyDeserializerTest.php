<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\PropertyDeserializer;
use Wikibase\DataModel\Entity\Property;

/**
 * @covers Wikibase\DataModel\Deserializers\PropertyDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$claimsDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );

		return new PropertyDeserializer( $entityIdDeserializerMock, $claimsDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'type' => 'property'
				)
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				array(
					'type' => 'item'
				)
			),
		);
	}

	public function deserializationProvider() {
		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );

		return array(
			array(
				$property,
				array(
					'type' => 'property',
					'datatype' => 'string'
				)
			),
		);
	}
}
