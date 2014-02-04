<?php

namespace Wikibase\Lib\Parsers;

use Language;
use ValueParsers\ParserOptions;
use ValueParsers\Unlocalizer;

/**
 * Class to unlocalize month names using Mediawiki's Language object
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class MonthNameUnlocalizer implements Unlocalizer {

	/**
	 * @see Unlocalizer::unlocalize()
	 *
	 * @param string $string string to process
	 * @param string $language language code
	 * @param ParserOptions $options
	 *
	 * @return string unlocalized string
	 */
	public function unlocalize( $string, $language, ParserOptions $options ) {
		if( $language === 'en' ) {
			return $string;
		}

		$lang = Language::factory( $language );
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

		for ( $i = 1; $i < 13; $i++ ) {
			$string = str_replace( $from->getMonthName( $i ), $to->getMonthName( $i ), $string );
		}

		if( $string !== $initialString ) {
			return $string;
		}

		for ( $i = 1; $i < 13; $i++ ) {
			$string = str_replace( $from->getMonthAbbreviation( $i ), $to->getMonthName( $i ), $string );
		}

		return $string;
	}
}