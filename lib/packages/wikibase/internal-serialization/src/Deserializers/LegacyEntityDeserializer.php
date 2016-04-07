<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyEntityDeserializer implements DispatchableDeserializer {

	/**
	 * @var Deserializer
	 */
	private $itemDeserializer;

	/**
	 * @var Deserializer
	 */
	private $propertyDeserializer;

	public function __construct( Deserializer $itemDeserializer, Deserializer $propertyDeserializer ) {
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

		if ( $this->isPropertySerialization( $serialization ) ) {
			return $this->propertyDeserializer->deserialize( $serialization );
		}

		return $this->itemDeserializer->deserialize( $serialization );
	}

	private function isPropertySerialization( $serialization ) {
		return array_key_exists( 'datatype', $serialization );
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
		return is_array( $serialization )
			// This element is called 'id' in the current serialization.
			&& array_key_exists( 'entity', $serialization );
	}

}
