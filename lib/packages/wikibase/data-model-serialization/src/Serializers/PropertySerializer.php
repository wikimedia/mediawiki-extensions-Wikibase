<?php

namespace Wikibase\DataModel\Serializers;

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
