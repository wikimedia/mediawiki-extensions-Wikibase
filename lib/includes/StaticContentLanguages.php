<?php

namespace Wikibase\Lib;

/**
 * Provide languages supported as content languages based on a list
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class StaticContentLanguages implements ContentLanguages {

	/**
	 * @var string[] Array of language codes
	 */
	private $languageMap = null;

	/**
	 * @param string[] $languageMap
	 */
	public function __construct( array $languageMap ) {
		$this->languageMap = $languageMap;
	}

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		return $this->languageMap;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public function hasLanguage( $languageCode ) {
		return in_array( $languageCode, $this->languageMap );
	}

}
