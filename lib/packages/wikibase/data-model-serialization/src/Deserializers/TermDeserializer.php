<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Term\Term;

/**
 * @since 1.5
 *
 * @author Adam Shorland
 */
class TermDeserializer implements Deserializer {

	/**
	 * @param mixed $serialization
	 *
	 * @return Term
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		$this->requireAttribute( $serialization, 'language' );
		$this->requireAttribute( $serialization, 'value' );
		// Do not deserialize term fallbacks
		$this->assertNotAttribute( $serialization, 'source' );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @return Term
	 */
	private function getDeserialized( $serialization ) {
		return new Term( $serialization['language'], $serialization['value'] );
	}

	/**
	 * @param array $serialization
	 */
	private function assertCanDeserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The term serialization should be an array' );
		}
	}

	/**
	 * @param array $serialization
	 * @param string $attribute
	 */
	private function requireAttribute( $serialization, $attribute ) {
		if ( !is_array( $serialization ) || !array_key_exists( $attribute, $serialization ) ) {
			throw new MissingAttributeException( $attribute );
		}
	}

	/**
	 * @param array $array
	 * @param string $key
	 */
	private function assertNotAttribute( array $array, $key ) {
		if ( array_key_exists( $key, $array ) ) {
			throw new InvalidAttributeException(
				$key,
				$array[$key],
				'Deserialization of attribute ' . $key . ' not supported.'
			);
		}
	}

}
