<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class FingerprintDeserializer implements Deserializer {

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param mixed $serialization
	 *
	 * @return object
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$fingerprint = new Fingerprint();

		$this->setLabelsFromSerialization( $serialization, $fingerprint );
		$this->setDescriptionsFromSerialization( $serialization, $fingerprint );
		$this->setAliasesFromSerialization( $serialization, $fingerprint );

		return $fingerprint;
	}

	private function setLabelsFromSerialization( array $serialization, Fingerprint $fingerprint ) {
		if ( !array_key_exists( 'labels', $serialization ) ) {
			return;
		}
		$this->assertAttributeIsArray( $serialization, 'labels' );

		$fingerprint->setLabels( $this->deserializeValuePerLanguageSerialization( $serialization['labels'] ) );
	}

	private function setDescriptionsFromSerialization( array $serialization, Fingerprint $fingerprint ) {
		if ( !array_key_exists( 'descriptions', $serialization ) ) {
			return;
		}
		$this->assertAttributeIsArray( $serialization, 'descriptions' );

		$fingerprint->setDescriptions( $this->deserializeValuePerLanguageSerialization( $serialization['descriptions'] ) );
	}

	private function deserializeValuePerLanguageSerialization( array $serialization ) {
		$termList = new TermList();

		foreach ( $serialization as $requestedLanguage => $valueSerialization ) {
			$this->assertIsValidValueSerialization( $valueSerialization, $requestedLanguage );
			$termList->setTextForLanguage( $valueSerialization['language'], $valueSerialization['value'] );
		}

		return $termList;
	}

	private function setAliasesFromSerialization( array $serialization, Fingerprint $fingerprint ) {
		if ( !array_key_exists( 'aliases', $serialization ) ) {
			return;
		}
		$this->assertAttributeIsArray( $serialization, 'aliases' );

		foreach ( $serialization['aliases'] as $requestedLanguage => $aliasesPerLanguageSerialization ) {
			if ( !is_array( $aliasesPerLanguageSerialization ) ) {
				throw new DeserializationException( "Aliases attribute should be an array of array" );
			}

			$aliases = array();

			foreach ( $aliasesPerLanguageSerialization as $aliasSerialization ) {
				$this->assertIsValidValueSerialization( $aliasSerialization, $requestedLanguage );
				$aliases[] = $aliasSerialization['value'];
			}

			$fingerprint->setAliasGroup( $requestedLanguage, $aliases );
		}
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

	private function assertRequestedAndActualLanguageMatch( $serialization, $requestedLanguage ) {
		if ( $serialization['language'] !== $requestedLanguage ) {
			throw new DeserializationException(
				'Deserialization of a value of the attribute language (actual)'
					. ' that is not matching the language key (requested) is not supported: '
					. $serialization['language'] . ' !== ' . $requestedLanguage
			);
		}
	}

	private function assertIsValidValueSerialization( $serialization, $requestedLanguage ) {
		$this->requireAttribute( $serialization, 'language' );
		$this->requireAttribute( $serialization, 'value' );
		$this->assertNotAttribute( $serialization, 'source' );

		$this->assertAttributeInternalType( $serialization, 'language', 'string' );
		$this->assertAttributeInternalType( $serialization, 'value', 'string' );
		$this->assertRequestedAndActualLanguageMatch( $serialization, $requestedLanguage );
	}

	protected function assertAttributeIsArray( array $array, $attributeName ) {
		$this->assertAttributeInternalType( $array, $attributeName, 'array' );
	}

	protected function assertAttributeInternalType( array $array, $attributeName, $internalType ) {
		if ( gettype( $array[$attributeName] ) !== $internalType ) {
			throw new InvalidAttributeException(
				$attributeName,
				$array[$attributeName],
				"The internal type of attribute '$attributeName' needs to be '$internalType'"
			);
		}
	}

}
