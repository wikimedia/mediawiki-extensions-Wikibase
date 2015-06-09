<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\Entity;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $legacyDeserializer;

	/**
	 * @var DispatchableDeserializer
	 */
	private $currentDeserializer;

	/**
	 * @var mixed
	 */
	private $serialization;

	public function __construct(
		Deserializer $legacyDeserializer,
		DispatchableDeserializer $currentDeserializer
	) {
		$this->legacyDeserializer = $legacyDeserializer;
		$this->currentDeserializer = $currentDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return Entity
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Entity serialization must be an array' );
		}

		$this->serialization = $serialization;

		if ( $this->isLegacySerialization() ) {
			return $this->fromLegacySerialization();
		}
		elseif ( $this->isCurrentSerialization() ) {
			return $this->fromCurrentSerialization();
		}
		else {
			return $this->fromUnknownSerialization();
		}
	}

	private function isLegacySerialization() {
		return array_key_exists( 'entity', $this->serialization );
	}

	private function isCurrentSerialization() {
		return $this->currentDeserializer->isDeserializerFor( $this->serialization );
	}

	private function fromLegacySerialization() {
		return $this->legacyDeserializer->deserialize( $this->serialization );
	}

	private function fromCurrentSerialization() {
		return $this->currentDeserializer->deserialize( $this->serialization );
	}

	private function fromUnknownSerialization() {
		try {
			return $this->fromLegacySerialization();
		} catch ( DeserializationException $legacyEx ) {
			try {
				return $this->fromCurrentSerialization();
			} catch ( DeserializationException $currentEx ) {
				throw new DeserializationException(
					'The provided entity serialization is neither legacy ('
					. $legacyEx->getMessage() . ') nor current ('
					. $currentEx->getMessage() . ')'
				);
			}
		}
	}

}
