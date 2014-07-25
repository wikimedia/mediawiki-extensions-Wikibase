<?php

namespace Wikibase\Lib\Parsers;

use Language;
use ValueParsers\ParserOptions;

/**
 * Class to unlocalize a month name in a date string using MediaWiki's Language object.
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
	 * @see NumberUnlocalizer::unlocalizeNumber
	 *
	 * @param string $date Localized date string.
	 * @param string $languageCode
	 * @param ParserOptions $options
	 *
	 * @return string
	 */
	public function unlocalize( $date, $languageCode, ParserOptions $options ) {
		$language = Language::factory( $languageCode );
		$en = Language::factory( 'en' );

		$date = $this->unlocalizeMonthName( $language, $en, $date );

		return $date;
	}

	/**
	 * Unlocalizes the longest month name in a date string that could be found first.
	 * Takes full month names, genitive names and abbreviations into account.
	 *
	 * @param Language $from
	 * @param Language $to
	 * @param string $date Localized date string.
	 *
	 * @return string
	 */
	private function unlocalizeMonthName( Language $from, Language $to, $date ) {
		// Replace month names in the $from language with the canonical name in the $to language.
		// Also replace all forms already in the target language with the canonical form.
		$replacements = array_merge(
			$this->getMonthNameMappings( $from, $to ),
			$this->getMonthNameMappings( $to, $to )
		);

		// Order search strings from longest to shortest
		uksort( $replacements, function( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );

		// Try replacing each mapping, longest first
		$date = $this->replace_first( $replacements, $date );
		return $date;
	}

	private function getMonthNameMappings( Language $from, Language $to ) {
		$replacements = array();

		for ( $i = 1; $i <= 12; $i++ ) {
			$replace = $to->getMonthName( $i );

			$replacements[$from->getMonthName( $i )] = $replace;
			$replacements[$from->getMonthNameGen( $i )] = $replace;
			$replacements[$from->getMonthAbbreviation( $i )] = $replace;
		}

		return $replacements;
	}

	/**
	 * Replaces the first key from $replacements found in $text
	 * with the corresponding value. If the key is found multiple
	 * times in $text, all occurrences are replaced.
	 *
	 * @param array $replacements An associative array representing the desired replacements.
	 * @param string $text
	 *
	 * @return string
	 */
	private function replace_first( array $replacements, $text ) {

		// Try each mapping, in order
		foreach ( $replacements as $search => $replace ) {
			$mangled = str_replace( $search, $replace, $text, $count );

			// Found a match, done
			if ( $count > 0 ) {
				return $mangled;
			}
		}

		return $text;
	}

}