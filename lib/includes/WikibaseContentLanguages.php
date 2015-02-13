<?php

namespace Wikibase\Lib;

use Language;
use Wikibase\Utils;

/**
 * Provide languages supported as content language based on Wikibase\Utils
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseContentLanguages implements ContentLanguages {

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		$languageCodes = array_keys( Language::fetchLanguageNames() );
		return $languageCodes;
	}

	/**
	 * Get the name of the language specified by $languageCode. The name should be in the language
	 * specified by $inLanguage, but it might be in any other language. If null is given as $inLanguage,
	 * $languageCode is used, i. e. the service tries to give the autonym of the language.
	 *
	 * @param string $languageCode
	 * @param string|null $inLanguage
	 *
	 * @return string
	 */
	public function getName( $languageCode, $inLanguage = null ) {
		return Utils::fetchLanguageName( $languageCode, $inLanguage );
	}

}
