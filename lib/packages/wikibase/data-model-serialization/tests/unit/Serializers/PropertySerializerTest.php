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
class PropertySerializerTest extends EntitySerializerBaseTest {

	public function buildSerializer() {
		return new PropertySerializer( $this->getClaimsSerializerMock() );
	}

	public function serializableProvider() {
		return array(
			array(
				Property::newEmpty()
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

	protected function buildEmptyEntity() {
		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		return $property;
	}

	protected function buildEmptyEntitySerialization() {
		return array(
			'type' => 'property',
			'datatype' => 'string'
		);
	}
}
