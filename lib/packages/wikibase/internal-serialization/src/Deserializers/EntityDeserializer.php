<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class EntityDeserializer implements Deserializer {

	/**
	 * @var DispatchableDeserializer
	 */
	private $legacyDeserializer;

	/**
	 * @var DispatchableDeserializer
	 */
	private $currentDeserializer;

	public function __construct(
		DispatchableDeserializer $legacyDeserializer,
		DispatchableDeserializer $currentDeserializer
	) {
		$this->legacyDeserializer = $legacyDeserializer;
		$this->currentDeserializer = $currentDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return EntityDocument
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Entity serialization must be an array' );
		}

		if ( $this->currentDeserializer->isDeserializerFor( $serialization ) ) {
			return $this->currentDeserializer->deserialize( $serialization );
		} elseif ( $this->legacyDeserializer->isDeserializerFor( $serialization ) ) {
			return $this->legacyDeserializer->deserialize( $serialization );
		} else {
			return $this->fromUnknownSerialization( $serialization );
		}
	}

	private function fromUnknownSerialization( array $serialization ) {
		try {
			return $this->currentDeserializer->deserialize( $serialization );
		} catch ( DeserializationException $currentEx ) {
			try {
				return $this->legacyDeserializer->deserialize( $serialization );
			} catch ( DeserializationException $legacyEx ) {
				throw new DeserializationException(
					'The provided entity serialization is neither legacy ('
					. $legacyEx->getMessage() . ') nor current ('
					. $currentEx->getMessage() . ')'
				);
			}
		}
	}

}
