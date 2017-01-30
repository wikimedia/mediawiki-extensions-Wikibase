<?php

namespace Wikibase\Lib;

use Language;

/**
 * Service for looking up language names based on MediaWiki's Language
 * class.
 *
 * @license GPL-2.0+
 */
class LanguageNameLookup {

	/**
	 * @var string|null
	 */
	private $inLanguage = null;

	/**
	 * @param string|null $inLanguage Language code of the language in which to return the language
	 *  names. Use null for autonyms (returns each language name in it's own language).
	 */
	public function __construct( $inLanguage = null ) {
		if ( $inLanguage !== null ) {
			$this->inLanguage = $this->normalize( $inLanguage );
		}
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getName( $languageCode ) {
		$languageCode = $this->normalize( $languageCode );
		$name = Language::fetchLanguageName( $languageCode, $this->inLanguage );

		if ( $name === '' ) {
			return $languageCode;
		}

		return $name;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function normalize( $languageCode ) {
		return str_replace( '_', '-', $languageCode );
	}

}
