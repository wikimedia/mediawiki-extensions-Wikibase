<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\PropertySerializer;

/**
 * @covers Wikibase\DataModel\Serializers\PropertySerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertySerializerTest extends SerializerBaseTest {

	public function buildSerializer() {
		$claimsSerializerMock = $this->getMock( 'Serializers\Serializer' );

		return new PropertySerializer( $claimsSerializerMock );
	}

	public function serializableProvider() {
		return array(
			array(
				Property::newFromType( 'string' )
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
				Item::newEmpty()
			),
		);
	}

	public function serializationProvider() {
		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );

		return array(
			array(
				array(
					'type' => 'property',
					'datatype' => 'string'
				),
				$property
			),
		);
	}
}
