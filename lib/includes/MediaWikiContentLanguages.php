<?php

namespace Wikibase\Lib;

use Language;

/**
 * Provide languages supported as content languages based on MediaWiki's Language class.
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Marius Hoch < hoo@online.de >
 */
class MediaWikiContentLanguages implements ContentLanguages {

	/**
	 * @var string[]|null Array of language codes => language names.
	 */
	private $languageMap = null;

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		$languageCodes = array_keys( $this->getLanguageMap() );
		return $languageCodes;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public function hasLanguage( $languageCode ) {
		return array_key_exists( $languageCode, $this->getLanguageMap() );
	}

	/**
	 * @return string[] Array of language codes => language names.
	 */
	private function getLanguageMap() {
		if ( $this->languageMap === null ) {
			$this->languageMap = Language::fetchLanguageNames();
		}

		return $this->languageMap;
	}

}
