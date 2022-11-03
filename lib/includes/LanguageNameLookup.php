<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use Language;

/**
 * Service for looking up language names based on MediaWiki's Language
 * class.
 *
 * @license GPL-2.0-or-later
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
	public function __construct( ?string $inLanguage = null ) {
		if ( $inLanguage !== null ) {
			$this->inLanguage = $this->normalize( $inLanguage );
		}
	}

	public function getName( string $languageCode ): string {
		$languageCode = $this->normalize( $languageCode );
		// TODO inject LanguageNameUtils
		$name = Language::fetchLanguageName( $languageCode, $this->inLanguage );

		if ( $name === '' ) {
			return $languageCode;
		}

		return $name;
	}

	private function normalize( string $languageCode ): string {
		return str_replace( '_', '-', $languageCode );
	}

}
