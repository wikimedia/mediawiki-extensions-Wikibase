<?php

namespace Wikibase\Lib\Parsers;

use Language;
use ValueParsers\ParserOptions;

/**
 * Class to unlocalise month names using Mediawiki's Language object
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class MonthNameUnlocalizer {

	/**
	 * @see Unlocalizer::unlocalize()
	 *
	 * @param string $string string to process
	 * @param string $languageCode
	 * @param ParserOptions $options
	 *
	 * @return string unlocalized string
	 */
	public function unlocalize( $string, $languageCode, ParserOptions $options ) {
		if ( $languageCode === 'en' ) {
			return $string;
		}

		$language = Language::factory( $languageCode );
		$en = Language::factory( 'en' );

		$string = $this->unlocalizeMonthNames( $language, $en, $string );

		return $string;
	}

	/**
	 * Unlocalizes month names in a string, checking full month names, genitives and abbreviations.
	 *
	 * @param Language $from
	 * @param Language $to
	 * @param string $string
	 *
	 * @return string
	 */
	private function unlocalizeMonthNames( Language $from, Language $to, $string ) {
		$replacements = array();

		for ( $i = 1; $i <= 12; $i++ ) {
			$replace = $to->getMonthName( $i );

			$replacements[$from->getMonthName( $i )] = $replace;
			$replacements[$from->getMonthNameGen( $i )] = $replace;
			$replacements[$from->getMonthAbbreviation( $i )] = $replace;
		}

		// Order search strings from longest to shortest
		uksort( $replacements, function( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );

		foreach ( $replacements as $search => $replace ) {
			$unlocalized = str_replace( $search, $replace, $string, $count );

			if ( $count === 1 ) {
				return $unlocalized;
			}
		}

		return $string;
	}

}
