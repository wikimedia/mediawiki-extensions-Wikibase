<?php

namespace Wikibase\Lib;

use Language;

/**
 * Service for looking up language names based on MediaWiki's Language
 * class.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class LanguageNameLookup {

	/**
	 * @since 0.5
	 *
	 * @param string $languageCode
	 * @param string|null $inLanguage Code of language in which to return the name (null for autonyms)
	 *
	 * @return string
	 */
	public function getName( $languageCode, $inLanguage = null ) {
		$languageCode = str_replace( '_', '-', $languageCode );

		if ( isset( $inLanguage ) ) {
			$inLanguage = str_replace( '_', '-', $inLanguage );
			$languageName = Language::fetchLanguageName( $languageCode, $inLanguage );
		}
		else {
			$languageName = Language::fetchLanguageName( $languageCode );
		}

		if ( $languageName === '' ) {
			$languageName = $languageCode;
		}

		return $languageName;
	}

}
