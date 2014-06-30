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

	/**
	 * Detects blobs that may be using a legacy serialization format.
	 * WikibaseRepo uses this for the $legacyExportFormatDetector parameter
	 * when constructing EntityHandlers.
	 *
	 * @see WikibaseRepo::newItemHandler
	 * @see WikibaseRepo::newPropertyHandler
	 * @see EntityHandler::__construct
	 *
	 * @note: False positives (detecting a legacy format when really no legacy format was used)
	 * are acceptable, false negatives (failing to detect a legacy format when one was used)
	 * are not acceptable.
	 *
	 * @param string $blob
	 * @param string $format
	 *
	 * @return bool True if $blob seems to be using a legacy serialization format.
	 */
	public static function isBlobUsingLegacyFormat( $blob, $format ) {
		// The legacy serialization uses something like "entity":["item",21] or
		// even "entity":"p21" for the entity ID.
		return preg_match( '/"entity"\s*:/', $blob ) > 0;
	}

}
