<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Serializers\Serializer as NewStyleSerializer;

/**
 * Serializer for generating the legacy serialization of an Entity.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LegacyInternalEntitySerializer implements NewStyleSerializer {

	/**
	 * Returns an array structure representing the given entity,
	 * by calling $entity->toArray().
	 *
	 * @see Serializer::getSerialized()
	 *
	 * @param Entity $entity
	 *
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function serialize( $entity ) {
		if ( !( $entity ) ) {
			throw new InvalidArgumentException( '$entity must be an Entity' );
		}

		return $entity->toArray();
	}

}
