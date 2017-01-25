<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use ApiUsageException;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Api\ApiErrorReporter;

/**
 * This class is used to validate attributes of term change serializations
 * such as language fields before they are passed to ChangeOps.
 *
 * @license GPL-2.0+
 */
class TermChangeOpSerializationValidator {

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	public function __construct( ContentLanguages $termsLanguages, ApiErrorReporter $errorReporter ) {
		$this->termsLanguages = $termsLanguages;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * @param string[] $arg The argument array to verify
	 * @param int|string $langCode The language code used in the value part. If $langCode is an integer
	 *                             $arg array must contain a 'language' key with a term language code as value.
	 *
	 * @throws ApiUsageException
	 */
	public function validateMultilangArgs( $arg, $langCode ) {
		$this->assertArray( $arg, 'An array was expected, but not found in the json for the '
			. "langCode $langCode" );

		if ( !array_key_exists( 'language', $arg ) ) {
			$this->errorReporter->dieError(
				"'language' was not found in the label or description json for $langCode",
				'missing-language' );
		}

		$this->assertString( $arg['language'], 'A string was expected, but not found in the json '
			. "for the langCode $langCode and argument 'language'" );
		if ( !is_numeric( $langCode ) ) {
			if ( $langCode !== $arg['language'] ) {
				$this->errorReporter->dieError(
					"inconsistent language: $langCode is not equal to {$arg['language']}",
					'inconsistent-language' );
			}
		}

		if ( !$this->termsLanguages->hasLanguage( $arg['language'] ) ) {
			$this->errorReporter->dieError( 'Unknown language: ' . $arg['language'], 'not-recognized-language' );
		}

		if ( !array_key_exists( 'remove', $arg ) ) {
			$this->assertString( $arg['value'], 'A string was expected, but not found in the json '
				. "for the langCode $langCode and argument 'value'" );
		}
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
			$this->errorReporter->dieError( $message, 'not-recognized-' . $type );
		}
	}

}
