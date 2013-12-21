<?php

namespace Wikibase\DataModel\Serializers\Snak;

use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyNoValueSnakSerializer implements Serializer {

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof PropertyNoValueSnak;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param mixed $object
	 *
	 * @return array
	 * @throws SerializationException
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'PropertyNoValueSnakSerializer can only serialize PropertyNoValueSnak objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( PropertyNoValueSnak $snak ) {
		return array (
			'snaktype' => $snak->getType(),
			'property' => $snak->getPropertyId()->getPrefixedId()
		);
	}
}
