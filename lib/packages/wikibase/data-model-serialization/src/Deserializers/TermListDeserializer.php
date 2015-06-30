<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class TermListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $termDeserializer;

	/**
	 * @param Deserializer $termDeserializer
	 */
	public function __construct( Deserializer $termDeserializer ) {
		$this->termDeserializer = $termDeserializer;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return TermList
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );
		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @return TermList
	 */
	private function getDeserialized( $serialization ) {
		$termList = new TermList();

		foreach ( $serialization as $termSerialization ) {
			$termList->setTerm( $this->termDeserializer->deserialize( $termSerialization ) );
		}

		return $termList;
	}

	/**
	 * @param array $serialization
	 */
	private function assertCanDeserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The term list serialization should be an array' );
		}

		foreach ( $serialization as $requestedLanguage => $valueSerialization ) {
			$this->assertAttributeIsArray( $serialization, $requestedLanguage );
			$this->assertRequestedAndActualLanguageMatch( $valueSerialization, $requestedLanguage );
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
