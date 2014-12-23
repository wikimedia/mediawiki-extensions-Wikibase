<?php

namespace Wikibase\Lib\Parsers;

use Language;

/**
 * Class to unlocalize a month name in a date string using MediaWiki's Language object.
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
	 * Unlocalizes the longest month name in a date string that could be found first.
	 * Tries to avoid doing multiple replacements and returns the localized original if in doubt.
	 * Takes full month names, genitive names and abbreviations into account.
	 *
	 * @see NumberUnlocalizer::unlocalizeNumber
	 *
	 * @param string $date Localized date string.
	 *
	 * @return string Unlocalized date string.
	 */
	public function unlocalize( $date ) {
		foreach ( $this->getReplacements() as $search => $replace ) {
			$unlocalized = str_replace( $search, $replace, $date, $count );

			// Nothing happened, try the next.
			if ( $count <= 0 ) {
				continue;
			}

			// Do not mess with strings that are clearly not a valid date.
			if ( $count > 1 ) {
				break;
			}

			// Do not mess with already unlocalized month names, e.g. "July" should not become
			// "Julyy" when replacing "Jul" with "July". But shortening "Julyus" to "July" is ok.
			if ( strpos( $date, $replace ) !== false && strlen( $replace ) >= strlen( $search ) ) {
				break;
			}

			return $unlocalized;
		}

		return $date;
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
			$this->baseLanguage = Language::factory( self::BASE_LANGUAGE_CODE );
		}

		return $this->baseLanguage;
	}

	/**
	 * @return Language|null
	 */
	private function getLanguage() {
		if ( $this->language === null && $this->languageCode !== null ) {
			$this->language = Language::factory( $this->languageCode );
		}

		return $this->language;
	}

}
