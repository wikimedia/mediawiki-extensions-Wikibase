<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Term\AliasGroup;

/**
 * Package private
 *
 * @author Adam Shorland
 */
class AliasGroupDeserializer implements Deserializer {

	public function __construct() {
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return AliasGroup
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @return AliasGroup
	 */
	private function getDeserialized( $serialization ) {
		foreach ( $serialization as $requestedLanguage => $aliasesPerLanguageSerialization ) {
			if ( !is_array( $aliasesPerLanguageSerialization ) ) {
				throw new DeserializationException( "Aliases serialization should be an array of array" );
			}

			$aliases = array();

			foreach ( $aliasesPerLanguageSerialization as $aliasSerialization ) {
				$this->assertIsValidValueSerialization( $aliasSerialization, $requestedLanguage );
				$aliases[] = $aliasSerialization['value'];
			}

			return new AliasGroup(
				$requestedLanguage,
				$aliases
			);
		}
	}

	/**
	 * @param array $serialization
	 */
	private function assertCanDeserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The alias group serialization should be an array' );
		}
		if( count( $serialization ) > 1 ) {
			throw new DeserializationException( 'The alias group serialization should only contain 1 element' );
		}
	}

	private function assertIsValidValueSerialization( $serialization, $requestedLanguage ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Term serializations must be arrays' );
		}

		$this->requireAttribute( $serialization, 'language' );
		$this->requireAttribute( $serialization, 'value' );
		$this->assertNotAttribute( $serialization, 'source' );

		$this->assertAttributeInternalType( $serialization, 'language', 'string' );
		$this->assertAttributeInternalType( $serialization, 'value', 'string' );
		$this->assertRequestedAndActualLanguageMatch( $serialization, $requestedLanguage );
	}

	private function requireAttribute( array $array, $attributeName ) {
		if ( !array_key_exists( $attributeName, $array ) ) {
			throw new MissingAttributeException(
				$attributeName
			);
		}
	}

	private function assertNotAttribute( array $array, $key ) {
		if ( array_key_exists( $key, $array ) ) {
			throw new InvalidAttributeException(
				$key,
				$array[$key],
				'Deserialization of attribute ' . $key . ' not supported.'
			);
		}
	}

	private function assertAttributeInternalType( array $array, $attributeName, $internalType ) {
		if ( gettype( $array[$attributeName] ) !== $internalType ) {
			throw new InvalidAttributeException(
				$attributeName,
				$array[$attributeName],
				"The internal type of attribute '$attributeName' needs to be '$internalType'"
			);
		}
	}

	private function assertRequestedAndActualLanguageMatch( array $serialization, $requestedLanguage ) {
		if ( $serialization['language'] !== $requestedLanguage ) {
			throw new DeserializationException(
				'Deserialization of a value of the attribute language (actual)'
				. ' that is not matching the language key (requested) is not supported: '
				. $serialization['language'] . ' !== ' . $requestedLanguage
			);
		}
	}

}
