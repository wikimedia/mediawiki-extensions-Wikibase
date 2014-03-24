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
	 * @param string $langCode
	 * @param ParserOptions $options
	 *
	 * @return string unlocalized string
	 */
	public function unlocalize( $string, $langCode, ParserOptions $options ) {
		if( $langCode === 'en' ) {
			return $string;
		}

		$lang = Language::factory( $langCode );
		$en = Language::factory( 'en' );

		$string = $this->unlocalizeMonthNames( $lang, $en, $string );

		return $string;
	}

	/**
	 * Unlocalizes month names in a string, checking both full month names and abbreviations
	 * @param Language $from
	 * @param Language $to
	 * @param string $string
	 *
	 * @return string
	 */
	private function unlocalizeMonthNames( Language $from, Language $to, $string ) {
		$initialString = $string;

		for ( $i = 1; $i <= 12; $i++ ) {
			$string = str_replace( $from->getMonthName( $i ), $to->getMonthName( $i ), $string );
		}

		if( $string !== $initialString ) {
			return $string;
		}

		for ( $i = 1; $i <= 12; $i++ ) {
			$string = str_replace( $from->getMonthAbbreviation( $i ), $to->getMonthName( $i ), $string );
		}

		return $string;
	}

}