<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\Property;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyDeserializer extends EntityDeserializer {

	/**
	 * @param Deserializer $entityIdDeserializer
	 * @param Deserializer $claimsDeserializer
	 */
	public function __construct( Deserializer $entityIdDeserializer, Deserializer $claimsDeserializer ) {
		parent::__construct( 'property', $entityIdDeserializer, $claimsDeserializer );
	}

	protected function getPartiallyDeserialized( array $serialization ) {
		$property = Property::newEmpty();

		$this->setDataTypeFromSerialization( $serialization, $property );

		return $property;
	}

	private function setDataTypeFromSerialization( array $serialization, Property $property ) {
		$this->requireAttribute( $serialization, 'datatype' );
		$this->assertAttributeInternalType( $serialization, 'datatype', 'string' );

		$property->setDataTypeId( $serialization['datatype'] );
	}
}
