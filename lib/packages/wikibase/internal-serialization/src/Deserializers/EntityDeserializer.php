<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\Entity;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeserializer implements Deserializer {

	private $legacyDeserializer;
	private $currentDeserializer;

	public function __construct( Deserializer $legacyDeserializer, Deserializer $currentDeserializer ) {
		$this->legacyDeserializer = $legacyDeserializer;
		$this->currentDeserializer = $currentDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return Entity
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {

	}

}