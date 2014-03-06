<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimDeserializer implements Deserializer {

	public function __construct() {
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return SnakList
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		throw new DeserializationException( 'SnakList serialization should be an array' );
	}

}