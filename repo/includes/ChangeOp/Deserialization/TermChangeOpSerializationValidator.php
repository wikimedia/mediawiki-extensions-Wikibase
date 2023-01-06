<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Lib\ContentLanguages;

/**
 * This class is used to validate attributes of term change serializations
 * such as language fields before they are passed to ChangeOps.
 *
 * @license GPL-2.0-or-later
 */
class TermChangeOpSerializationValidator {

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	public function __construct( ContentLanguages $termsLanguages ) {
		$this->termsLanguages = $termsLanguages;
	}

	/**
	 * @param string[] $serialization Term serialization array
	 * @param int|string $languageCode Key from the term list array, related to $serialization.
	 *                                 If a string its value must match $serialization['language'].
	 *
	 * @see @ref docs_topics_changeop-serializations for information on term serialization format
	 *
	 * @throws ChangeOpDeserializationException
	 */
	public function validateTermSerialization( $serialization, $languageCode ) {
		$this->assertArray(
			$serialization,
			'An array was expected, but not found in term serialization for the language code ' . $languageCode
		);

		if ( !array_key_exists( 'language', $serialization ) ) {
			$this->throwException(
				"'language' was not found in term serialization for $languageCode",
				'missing-language'
			);
		}

		$this->assertString(
			$serialization['language'],
			'A string was expected, but not found in term serialization '
				. "for the language code $languageCode and key 'language'"
		);

		if ( !is_numeric( $languageCode ) ) {
			if ( $languageCode !== $serialization['language'] ) {
				$this->throwException(
					"inconsistent language in term serialization: $languageCode is not equal to {$serialization['language']}",
					'inconsistent-language',
					[ $serialization['language'], $languageCode ]
				);
			}
		}

		if ( !$this->termsLanguages->hasLanguage( $serialization['language'] ) ) {
			$this->throwException(
				'Unknown language: ' . $serialization['language'],
				'not-recognized-language',
				[ $serialization['language'] ]
			);
		}

		if ( !array_key_exists( 'remove', $serialization ) ) {
			$this->assertString(
				$serialization['value'],
				'A string was expected, but not found in the term serialization '
					. "for the language code $languageCode and key 'value'"
			);
		}
	}

	/**
	 * @param string $message
	 * @param string $errorCode
	 * @param array $params
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function throwException( $message, $errorCode, array $params = [] ) {
		throw new ChangeOpDeserializationException( $message, $errorCode, $params );
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertArray( $value, $message ) {
		$this->assertType( 'array', $value, $message );
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertString( $value, $message ) {
		$this->assertType( 'string', $value, $message );
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertType( $type, $value, $message ) {
		if ( gettype( $value ) !== $type ) {
			$this->throwException( $message, 'not-recognized-' . $type );
		}
	}

}
