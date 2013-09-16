<?php

namespace Wikibase;

use \Language;

/**
 * FIXME: this class is not a language fallback chain. It takes and uses a fallback chain.
 * The name thus needs to be updated to not be misleading.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 */
class LanguageFallbackChain {

	/**
	 * @var LanguageWithConversion[]
	 */
	private $chain = array();

	/**
	 * Constructor
	 *
	 * hm
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
	 * @param string[] $data Multilingual data with language codes as keys
	 *
	 * @return null|array of three items: array(
	 * 	'value' => finally fetched and translated value
	 * 	'language' => language code of the language which final value is in
	 * 	'source' => language code of the language where the value is translated from
	 * ), or null when no "acceptable" data can be found.
	 */
	public function extractPreferredValue( $data ) {

		foreach ( $this->chain as $languageWithConversion ) {
			$fetchCode = $languageWithConversion->getFetchLanguageCode();

			if ( isset( $data[$fetchCode] ) ) {
				return array(
					'value' => $languageWithConversion->translate( $data[$fetchCode] ),
					'language' => $languageWithConversion->getLanguageCode(),
					'source' => $languageWithConversion->getSourceLanguageCode(),
				);
			}
		}

		return null;
	}

	/**
	 * Try to fetch the best value in a multilingual data array first.
	 * If no "acceptable" value exists, return any value known.
	 *
	 * @param string[] $data Multilingual data with language codes as keys
	 *
	 * @return null|array of three items: array(
	 * 	'value' => finally fetched and translated value
	 * 	'language' => language code of the language which final value is in
	 * 	'source' => language code of the language where the value is translated from
	 * ), or null when no data with a valid language code can be found.
	 */
	public function extractPreferredValueOrAny( $data ) {
		$preferred = $this->extractPreferredValue( $data );

		if ( $preferred ) {
			return $preferred;
		}

		foreach ( $data as $code => $value ) {
			if ( Language::isValidCode( $code ) ) {
				return array(
					'value' => $value,
					'language' => $code,
					'source' => null,
				);
			}
		}

		return null;
	}
}
