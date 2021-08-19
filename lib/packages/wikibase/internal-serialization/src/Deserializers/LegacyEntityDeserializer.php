<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyEntityDeserializer implements DispatchableDeserializer {

	/**
	 * @var DispatchableDeserializer
	 */
	private $itemDeserializer;

	/**
	 * @var DispatchableDeserializer
	 */
	private $propertyDeserializer;

	public function __construct(
		DispatchableDeserializer $itemDeserializer,
		DispatchableDeserializer $propertyDeserializer
	) {
		$this->itemDeserializer = $itemDeserializer;
		$this->propertyDeserializer = $propertyDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return EntityDocument
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Entity serialization must be an array' );
		}

		if ( $this->propertyDeserializer->isDeserializerFor( $serialization ) ) {
			return $this->propertyDeserializer->deserialize( $serialization );
		}

		return $this->itemDeserializer->deserialize( $serialization );
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
		return $this->itemDeserializer->isDeserializerFor( $serialization )
			|| $this->propertyDeserializer->isDeserializerFor( $serialization );
	}

}
