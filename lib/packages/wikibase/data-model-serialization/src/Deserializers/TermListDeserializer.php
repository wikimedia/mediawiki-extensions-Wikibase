<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class TermListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $termDeserializer;

	public function __construct( Deserializer $termDeserializer ) {
		$this->termDeserializer = $termDeserializer;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array[] $serialization
	 *
	 * @throws DeserializationException
	 * @return TermList
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The term list serialization should be an array' );
		}

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array[] $serialization
	 *
	 * @return TermList
	 */
	private function getDeserialized( array $serialization ) {
		$termList = new TermList();

		foreach ( $serialization as $requestedLanguage => $termSerialization ) {
			$this->assertAttributeIsArray( $serialization, $requestedLanguage );
			$this->assertRequestedAndActualLanguageMatch( $termSerialization, $requestedLanguage );

			/** @var Term $term */
			$term = $this->termDeserializer->deserialize( $termSerialization );
			$termList->setTerm( $term );
		}

		return $termList;
	}

	private function assertRequestedAndActualLanguageMatch(
		array $serialization,
		$requestedLanguage
	) {
		if ( strcmp( $serialization['language'], $requestedLanguage ) !== 0 ) {
			throw new DeserializationException(
				'Deserialization of a value of the attribute language (actual)'
					. ' that is not matching the language key (requested) is not supported: '
					. $serialization['language'] . ' !== ' . $requestedLanguage
			);
		}
	}

	private function assertAttributeIsArray( array $array, $attributeName ) {
		if ( !is_array( $array[$attributeName] ) ) {
			throw new InvalidAttributeException(
				$attributeName,
				$array[$attributeName],
				"The internal type of attribute '$attributeName' needs to be 'array'"
			);
		}
	}

}
