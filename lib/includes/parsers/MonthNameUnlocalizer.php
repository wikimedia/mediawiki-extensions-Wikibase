<?php

namespace Wikibase\Lib\Parsers;

use Language;

/**
 * Class to unlocalise month names using MediaWiki's Language object.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 *
 * @todo move me to DataValues-time
 */
class MonthNameUnlocalizer {

	const BASE_LANGUAGE_CODE = 'en';

	/**
	 * @var string|null Language code of the source strings or null if it's the base language.
	 */
	private $languageCode = null;

	private $baseLanguage = null;
	private $language = null;

	/**
	 * @param string $languageCode
	 */
	public function __construct( $languageCode ) {
		if ( $languageCode !== self::BASE_LANGUAGE_CODE ) {
			$this->languageCode = $languageCode;
		}
	}

	/**
	 * Unlocalizes month names in a string, checking full month names, genitives and abbreviations.
	 *
	 * @param string $string Localized string to process.
	 *
	 * @return string Unlocalized string.
	 */
	public function unlocalize( $string ) {
		foreach ( $this->getReplacements() as $search => $replace ) {
			$unlocalized = str_replace( $search, $replace, $string, $count );

			if ( $count === 1 ) {
				return $unlocalized;
			}
		}

		return $string;
	}

	private function getReplacements() {
		$replacements = array();
		$language = $this->getLanguage();

		if ( $language !== null ) {
			$baseLanguage = $this->getBaseLanguage();

			for ( $i = 1; $i <= 12; $i++ ) {
				$replace = $baseLanguage->getMonthName( $i );

				$replacements[$language->getMonthName( $i )] = $replace;
				$replacements[$language->getMonthNameGen( $i )] = $replace;
				$replacements[$language->getMonthAbbreviation( $i )] = $replace;
			}

			// Order search strings from longest to shortest
			uksort( $replacements, function( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			} );
		}

		return $replacements;
	}

	private function getBaseLanguage() {
		if ( $this->baseLanguage === null ) {
			// Lazy initialization for performance reasons, Language objects are slooow
			$this->baseLanguage = Language::factory( self::BASE_LANGUAGE_CODE );
		}

		return $this->baseLanguage;
	}

	/**
	 * @return Language|null
	 */
	private function getLanguage() {
		if ( $this->language === null && $this->languageCode !== null ) {
			// Lazy initialization for performance reasons, Language objects are slooow
			$this->language = Language::factory( $this->languageCode );
		}

		return $this->language;
	}

}
