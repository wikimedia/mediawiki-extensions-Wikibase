<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use MediaWiki\Languages\LanguageNameUtils;

/**
 * Service for looking up language names based on MediaWiki's Language
 * class.
 *
 * @license GPL-2.0-or-later
 */
class LanguageNameLookup {

	private LanguageNameUtils $languageNameUtils;

	/**
	 * @var string|null
	 */
	private $inLanguage;

	/**
	 * @param LanguageNameUtils $languageNameUtils
	 * @param string|null $inLanguage Language code of the language in which to return the language
	 *  names. Use LanguageNameUtils::AUTONYMS for autonyms (returns each language name in it's own language).
	 */
	public function __construct(
		LanguageNameUtils $languageNameUtils,
		?string $inLanguage
	) {
		$this->languageNameUtils = $languageNameUtils;
		if ( $inLanguage !== LanguageNameUtils::AUTONYMS ) {
			$inLanguage = $this->normalize( $inLanguage );
		}
		$this->inLanguage = $inLanguage;
	}

	public function getName( string $languageCode ): string {
		$languageCode = $this->normalize( $languageCode );
		$name = $this->languageNameUtils->getLanguageName( $languageCode, $this->inLanguage );

		if ( $name === '' ) {
			return $languageCode;
		}

		return $name;
	}

	private function normalize( string $languageCode ): string {
		return str_replace( '_', '-', $languageCode );
	}

}
