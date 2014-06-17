<?php

namespace Wikibase;

use Language;

/**
 * FIXME: this class is not a language fallback chain. It takes and uses a fallback chain.
 * The name thus needs to be updated to not be misleading.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 */
class LanguageFallbackChain {

	/**
	 * @var LanguageWithConversion[]
	 */
	private $chain;

	/**
	 * @param LanguageWithConversion[] $chain
	 */
	public function __construct( array $chain ) {
		$this->chain = $chain;
	}

	/**
	 * Get raw fallback chain as an array. Semi-private for testing.
	 *
	 * @return LanguageWithConversion[]
	 */
	public function getFallbackChain() {
		return $this->chain;
	}

	/**
	 * Try to fetch the best value in a multilingual data array.
	 *
	 * @param string[]|array[] $data Multilingual data with language codes as keys
	 *
	 * @return string[]|null of three items: array(
	 * 	'value' => finally fetched and translated value
	 * 	'language' => language code of the language which final value is in
	 * 	'source' => language code of the language where the value is translated from
	 * ), or null when no "acceptable" data can be found.
	 */
	public function extractPreferredValue( array $data ) {
		foreach ( $this->chain as $languageWithConversion ) {
			$languageCode = $languageWithConversion->getFetchLanguageCode();

			if ( isset( $data[$languageCode] ) ) {
				// Return pre-build data from an EntityInfoBuilder as it is
				if ( is_array( $data[$languageCode] ) ) {
					return $data[$languageCode];
				} else {
					return array(
						'value' => $languageWithConversion->translate( $data[$languageCode] ),
						'language' => $languageWithConversion->getLanguageCode(),
						'source' => $languageWithConversion->getSourceLanguageCode(),
					);
				}
			}
		}

		return null;
	}

	/**
	 * Try to fetch the best value in a multilingual data array first.
	 * If no "acceptable" value exists, return any value known.
	 *
	 * @param string[]|array[] $data Multilingual data with language codes as keys
	 *
	 * @return string[]|null of three items: array(
	 * 	'value' => finally fetched and translated value
	 * 	'language' => language code of the language which final value is in
	 * 	'source' => language code of the language where the value is translated from
	 * ), or null when no data with a valid language code can be found.
	 */
	public function extractPreferredValueOrAny( array $data ) {
		$preferred = $this->extractPreferredValue( $data );

		if ( $preferred !== null ) {
			return $preferred;
		}

		foreach ( $data as $languageCode => $value ) {
			if ( Language::isValidCode( $languageCode ) ) {
				if ( is_array( $value ) ) {
					return $value;
				} else {
					return array(
						'value' => $value,
						'language' => $languageCode,
						'source' => null,
					);
				}
			}
		}

		return null;
	}

}
