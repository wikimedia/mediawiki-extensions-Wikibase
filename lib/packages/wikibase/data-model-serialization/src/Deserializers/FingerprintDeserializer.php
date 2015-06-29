<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 * @author Adam Shorland
 */
class FingerprintDeserializer implements Deserializer {

	/**
	 * @var TermListDeserializer
	 */
	private $termListDeserializer;

	/**
	 * @var AliasGroupDeserializer
	 */
	private $aliasGroupDeserializer;

	public function __construct() {
		$this->termListDeserializer = new TermListDeserializer( new TermDeserializer() );
		$this->aliasGroupDeserializer = new AliasGroupDeserializer();
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return Fingerprint
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Fingerprint serialization must be an array' );
		}

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
		foreach ( $serialization as $requestedLanguage => $valueSerialization ) {
			$this->assertAttributeIsArray( $serialization, $requestedLanguage );
			$this->assertRequestedAndActualLanguageMatch( $valueSerialization, $requestedLanguage );
		}

		return $this->termListDeserializer->deserialize( $serialization );
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

			$fingerprint->getAliasGroups()->setGroup(
				$this->aliasGroupDeserializer->deserialize(
					array( $requestedLanguage => $aliasesPerLanguageSerialization )
				)
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

	private function assertAttributeIsArray( array $array, $attributeName ) {
		$this->assertAttributeInternalType( $array, $attributeName, 'array' );
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

}
