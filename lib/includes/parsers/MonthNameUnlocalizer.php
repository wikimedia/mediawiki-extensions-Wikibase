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
 *
 * @todo move me to DataValues-time
 */
class MonthNameUnlocalizer {

	/**
	 * @see NumberUnlocalizer::unlocalizeNumber
	 *
	 * @param string $date Localized date string.
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function unlocalize( $date, $languageCode ) {
		if ( $languageCode === 'en' ) {
			return $date;
		}

		$language = Language::factory( $languageCode );
		$en = Language::factory( 'en' );

		$date = $this->unlocalizeMonthName( $language, $en, $date );

		return $date;
	}

	/**
	 * Unlocalizes the longest month name in a date string that could be found first.
	 * Tries to avoid doing multiple replacements and returns the localized original if in doubt.
	 * Takes full month names, genitive names and abbreviations into account.
	 *
	 * @param Language $from
	 * @param Language $to
	 * @param string $date Localized date string.
	 *
	 * @return string
	 */
	private function unlocalizeMonthName( Language $from, Language $to, $date ) {
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
			$unlocalized = str_replace( $search, $replace, $date, $count );

			// Nothing happened, try the next.
			if ( $count <= 0 ) {
				continue;
			}

			// Do not mess with strings that are clearly not a valid date.
			if ( $count > 1 ) {
				break;
			}

			// Do not mess with already unlocalized month names, e.g. "Juli" should not become
			// "Julyi" when replacing "Jul" with "July". But shortening "Julyus" to "July" is ok.
			if ( strpos( $date, $replace ) !== false && strlen( $replace ) >= strlen( $search ) ) {
				break;
			}

			return $unlocalized;
		}

		return $date;
	}

}
