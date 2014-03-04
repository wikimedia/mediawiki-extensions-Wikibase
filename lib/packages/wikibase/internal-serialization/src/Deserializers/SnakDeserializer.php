<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Snak\Snak;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializer implements Deserializer {

	/**
	 * @param mixed $serialization
	 *
	 * @return Snak
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		// TODO: Implement deserialize() method.
	}

	/**
	 * @since 1.0
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		return false;
	}

}