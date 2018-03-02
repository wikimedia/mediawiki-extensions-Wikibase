<?php

namespace Wikibase\Lib;

/**
 * Provide languages supported as content languages based on two ContentLanguages
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class UnionContentLanguages implements ContentLanguages {

	/**
	 * @var ContentLanguages
	 */
	private $a;

	/**
	 * @var ContentLanguages
	 */
	private $b;

	/**
	 * @var string[]|null Array of language codes
	 */
	private $languageCodes = null;

	public function __construct( ContentLanguages $a, ContentLanguages $b ) {
		$this->a = $a;
		$this->b = $b;
	}

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		return $this->getLanguageCodes();
	}

	/**
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public function hasLanguage( $languageCode ) {
		return in_array( $languageCode, $this->getLanguageCodes() );
	}

	/**
	 * @return string[] Array of language codes
	 */
	private function getLanguageCodes() {
		if ( $this->languageCodes === null ) {
			$this->languageCodes = array_values( array_unique( array_merge( $this->a->getLanguages(), $this->b->getLanguages() ) ) );
		}

		return $this->languageCodes;
	}

}
