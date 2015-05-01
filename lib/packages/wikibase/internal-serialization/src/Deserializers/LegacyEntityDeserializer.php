<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\Entity;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyEntityDeserializer implements Deserializer {

	private $itemDeserializer;
	private $propertyDeserializer;

	public function __construct( Deserializer $itemDeserializer, Deserializer $propertyDeserializer ) {
		$this->itemDeserializer = $itemDeserializer;
		$this->propertyDeserializer = $propertyDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return Entity
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( $this->isPropertySerialization( $serialization ) ) {
			return $this->propertyDeserializer->deserialize( $serialization );
		}

		return $this->itemDeserializer->deserialize( $serialization );
	}

	private function isPropertySerialization( $serialization ) {
		return array_key_exists( 'datatype', $serialization );
	}

}
