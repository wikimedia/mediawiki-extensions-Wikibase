<?php

namespace Wikibase\Lib\Parsers;

use InvalidArgumentException;
use Language;

/**
 * Class to unlocalize a month name in a date string using MediaWiki's Language object.
 * Takes full month names, genitive names and abbreviations into account.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class MwMonthNameUnlocalizer extends MonthNameUnlocalizer {

	/**
	 * @param string $languageCode Language code of the source strings to be unlocalized.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $languageCode ) {
		if ( !is_string( $languageCode ) || $languageCode === '' ) {
			throw new InvalidArgumentException( '$languageCode must be a non-empty string' );
		}

		if ( $languageCode !== self::BASE_LANGUAGE_CODE ) {
			parent::__construct( $this->getReplacements( $languageCode ) );
		}
	}

	private function getReplacements( $languageCode ) {
		$replacements = array();

		$baseLanguage = Language::factory( self::BASE_LANGUAGE_CODE );
		$language = Language::factory( $languageCode );

		for ( $i = 1; $i <= 12; $i++ ) {
			$canonical = $baseLanguage->getMonthName( $i );

			$replacements[$language->getMonthName( $i )] = $canonical;
			$replacements[$language->getMonthNameGen( $i )] = $canonical;
			$replacements[$language->getMonthAbbreviation( $i )] = $canonical;
		}

		return $replacements;
	}

}
