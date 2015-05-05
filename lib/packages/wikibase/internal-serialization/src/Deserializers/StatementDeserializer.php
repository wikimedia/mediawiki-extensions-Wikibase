<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Claim\Claim;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class StatementDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $legacyDeserializer;

	/**
	 * @var DispatchableDeserializer
	 */
	private $currentDeserializer;

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
	 * @return Claim
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Claim serialization must be an array' );
		}

		if ( $this->isLegacySerialization( $serialization ) ) {
			return $this->fromLegacySerialization( $serialization );
		} elseif ( $this->isCurrentSerialization( $serialization ) ) {
			return $this->fromCurrentSerialization( $serialization );
		} else {
			return $this->fromUnknownSerialization( $serialization );
		}
	}

	private function isLegacySerialization( array $serialization ) {
		// This element is called 'mainsnak' in the current serialization.
		return array_key_exists( 'm', $serialization );
	}

	private function isCurrentSerialization( array $serialization ) {
		return $this->currentDeserializer->isDeserializerFor( $serialization );
	}

	private function fromLegacySerialization( array $serialization ) {
		return $this->legacyDeserializer->deserialize( $serialization );
	}

	private function fromCurrentSerialization( array $serialization ) {
		return $this->currentDeserializer->deserialize( $serialization );
	}

	private function fromUnknownSerialization( array $serialization ) {
		try {
			return $this->fromLegacySerialization( $serialization );
		} catch ( DeserializationException $legacyEx ) {
			try {
				return $this->fromCurrentSerialization( $serialization );
			} catch ( DeserializationException $currentEx ) {
				throw new DeserializationException(
					'The provided claim serialization is neither legacy ('
					. $legacyEx->getMessage() . ') nor current ('
					. $currentEx->getMessage() . ')'
				);
			}
		}
	}

}
