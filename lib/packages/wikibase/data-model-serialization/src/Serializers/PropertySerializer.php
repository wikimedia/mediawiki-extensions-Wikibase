<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Property;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertySerializer extends EntitySerializer {

	/**
	 * @param Serializer $claimsSerializer
	 */
	public function __construct( Serializer $claimsSerializer ) {
		parent::__construct( $claimsSerializer );
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof Property;
	}

	protected function getSpecificSerialization( Entity $entity ) {
		return array(
			'datatype' => $entity->getDataTypeId()
		);
	}
}
