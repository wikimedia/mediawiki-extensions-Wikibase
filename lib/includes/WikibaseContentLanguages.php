<?php

namespace Wikibase\Lib;

use Language;

/**
 * Provide languages supported as content languages based on MediaWiki's Language class.
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

}
