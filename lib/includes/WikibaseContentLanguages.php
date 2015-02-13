<?php

namespace Wikibase\Lib;

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
		return Utils::getLanguageCodes();
	}

	/**
	 * @return string $languageCode
	 */
	public function hasLanguage( $languageCode ) {
		return in_array( $languageCode, $this->getLanguages() );
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
