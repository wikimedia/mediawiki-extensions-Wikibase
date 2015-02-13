<?php

namespace Wikibase;

use Language;
use MWException;

/**
 * Utility functions for Wikibase.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author John Erling Blad < jeblad@gmail.com >
 */
final class Utils {

	/**
	 * Returns a list of language codes that Wikibase supports,
	 * ie the languages that a label or description can be in.
	 *
	 * @since 0.1
	 *
	 * @throws MWException if the list can not be obtained.
	 * @return string[]
	 */
	public static function getLanguageCodes() {
		static $languageCodes = null;

		if ( $languageCodes === null ) {
			$languageCodes = array_keys( Language::fetchLanguageNames() );

			if ( empty( $languageCodes ) ) {
				throw new MWException( 'List of language names is empty' );
			}
		}

		return $languageCodes;
	}

	/**
	 * @see Language::fetchLanguageName()
	 *
	 * @since 0.1
	 *
	 * @param string $languageCode
	 * @param string|null $inLanguage
	 *
	 * @return string
	 */
	public static function fetchLanguageName( $languageCode, $inLanguage = null ) {
		$languageCode = str_replace( '_', '-', $languageCode );

		if ( isset( $inLanguage ) ) {
			$inLanguage = str_replace( '_', '-', $inLanguage );
			$languageName = Language::fetchLanguageName( $languageCode, $inLanguage );
		}
		else {
			$languageName = Language::fetchLanguageName( $languageCode );
		}

		if ( $languageName == '' ) {
			$languageName = $languageCode;
		}

		return $languageName;
	}

}
