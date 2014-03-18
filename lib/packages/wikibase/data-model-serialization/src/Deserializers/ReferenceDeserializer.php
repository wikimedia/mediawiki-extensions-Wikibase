<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Reference;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceDeserializer implements DispatchableDeserializer {

	/**
	 * @var Deserializer
	 */
	private $snaksDeserializer;

	/**
	 * @param Deserializer $snaksDeserializer
	 */
	public function __construct( Deserializer $snaksDeserializer ) {
		$this->snaksDeserializer = $snaksDeserializer;
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		return $this->isValidSerialization( $serialization );
	}

	private function isValidSerialization( $serialization ) {
		return is_array( $serialization ) && array_key_exists( 'snaks', $serialization );
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param mixed $serialization
	 *
	 * @return object
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		return new Reference(
			$this->snaksDeserializer->deserialize( $serialization['snaks'] )
		);
	}

	private function assertCanDeserialize( $serialization ) {
		if ( !$this->isValidSerialization( $serialization ) ) {
			throw new DeserializationException( 'The serialization is invalid!' );
		}
	}
}
