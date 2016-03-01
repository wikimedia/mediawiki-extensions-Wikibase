<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class EntityDeserializer implements DispatchableDeserializer {

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
		if ( $this->currentDeserializer->isDeserializerFor( $serialization ) ) {
			return $this->currentDeserializer->deserialize( $serialization );
		} elseif ( $this->legacyDeserializer->isDeserializerFor( $serialization ) ) {
			return $this->legacyDeserializer->deserialize( $serialization );
		}

		throw new DeserializationException(
			'The provided entity serialization is neither legacy nor current'
		);
	}

	/**
	 * @see DispatchableDeserializer::isDeserializerFor
	 *
	 * @since 2.2
	 *
	 * @param mixed $serialization
	 *
	 * @return bool
	 */
	public function isDeserializerFor( $serialization ) {
		return $this->currentDeserializer->isDeserializerFor( $serialization )
			|| $this->legacyDeserializer->isDeserializerFor( $serialization );
	}

}
