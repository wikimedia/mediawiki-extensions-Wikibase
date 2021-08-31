<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Term\Term;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class TermDeserializer implements Deserializer {

	/**
	 * @param string[] $serialization
	 *
	 * @return Term
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );
		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param string[] $serialization
	 *
	 * @return Term
	 */
	private function getDeserialized( array $serialization ) {
		return new Term( $serialization['language'], $serialization['value'] );
	}

	/**
	 * @param string[] $serialization
	 *
	 * @throws DeserializationException
	 */
	private function assertCanDeserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The term serialization should be an array' );
		}

		$this->requireAttribute( $serialization, 'language' );
		$this->requireAttribute( $serialization, 'value' );
		// Do not deserialize term fallbacks
		$this->assertNotAttribute( $serialization, 'source' );

		$this->assertAttributeIsString( $serialization, 'language' );
		$this->assertAttributeIsString( $serialization, 'value' );
	}

	private function assertAttributeIsString( array $array, $attributeName ) {
		if ( !is_string( $array[$attributeName] ) ) {
			throw new InvalidAttributeException(
				$attributeName,
				$array[$attributeName],
				"The internal type of attribute '$attributeName' needs to be 'string'"
			);
		}
	}

	/**
	 * @param string[] $serialization
	 * @param string $attribute
	 *
	 * @throws MissingAttributeException
	 */
	private function requireAttribute( $serialization, $attribute ) {
		if ( !is_array( $serialization ) || !array_key_exists( $attribute, $serialization ) ) {
			throw new MissingAttributeException( $attribute );
		}
	}

	/**
	 * @param string[] $array
	 * @param string $key
	 *
	 * @throws InvalidAttributeException
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
