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
	 * @var string|null
	 */
	private $languageMap;

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		$languageCodes = array_keys( $this->getLanguageMap() );
		return $languageCodes;
	}

	/**
	 * @return string $languageCode
	 *
	 * @return bool
	 */
	public function hasLanguage( $languageCode ) {
		return array_key_exists( $languageCode, $this->getLanguageMap() );
	}

	private function getLanguageMap() {
		if ( $this->languageMap === null ) {
			$this->languageMap = Language::fetchLanguageNames();
		}

		return $this->languageMap;
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
