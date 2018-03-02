<?php

namespace Wikibase\Lib;

/**
 * Provide languages supported as content languages by removing values in one ContentLanguages
 * from another ContentLanguages
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class DifferenceContentLanguages implements ContentLanguages {

	/**
	 * @var ContentLanguages
	 */
	private $all;

	/**
	 * @var ContentLanguages
	 */
	private $excluded;

	/**
	 * @var string[]|null Array of language codes
	 */
	private $languageCodes = null;

	public function __construct( ContentLanguages $all, ContentLanguages $excluded ) {
		$this->all = $all;
		$this->excluded = $excluded;
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
			$this->languageCodes = array_values(
				array_diff( $this->all->getLanguages(), $this->excluded->getLanguages() )
			);
		}

		return $this->languageCodes;
	}

}
