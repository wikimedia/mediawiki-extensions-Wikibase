<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\Property;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyDeserializer extends EntityDeserializer {

	/**
	 * @param Deserializer $entityIdDeserializer
	 * @param Deserializer $fingerprintDeserializer
	 * @param Deserializer $claimsDeserializer
	 */
	public function __construct( Deserializer $entityIdDeserializer, Deserializer $fingerprintDeserializer, Deserializer $claimsDeserializer ) {
		parent::__construct( 'property', $entityIdDeserializer, $fingerprintDeserializer, $claimsDeserializer );
	}

	protected function getPartiallyDeserialized( array $serialization ) {
		$this->requireAttribute( $serialization, 'datatype' );
		$this->assertAttributeInternalType( $serialization, 'datatype', 'string' );
		$property = Property::newFromType( $serialization['datatype'] );

		return $property;
	}

}
