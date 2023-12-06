<?php

namespace Wikibase\Lib;

use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MediaWikiServices;

/**
 * Provide languages supported as content languages based on MediaWiki's LanguageNameUtils.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Marius Hoch < hoo@online.de >
 */
class MediaWikiContentLanguages implements ContentLanguages {

	/** @var LanguageNameUtils */
	private $languageNameUtils;

	private string $languageNameUtilsInclude;

	/**
	 * @var string[]|null Array of language codes => language names.
	 */
	private $languageMap = null;

	/**
	 * @param null|LanguageNameUtils $languageNameUtils
	 * @param string $languageNameUtilsInclude Either LanguageNameUtils::DEFINED,
	 *     LanguageNameUtils::ALL, or LanguageNameUtils::SUPPORTED.
	 */
	public function __construct(
		?LanguageNameUtils $languageNameUtils = null,
		string $languageNameUtilsInclude = LanguageNameUtils::DEFINED
	) {
		$this->languageNameUtils = $languageNameUtils ?:
			MediaWikiServices::getInstance()->getLanguageNameUtils();
		$this->languageNameUtilsInclude = $languageNameUtilsInclude;
	}

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages() {
		return array_keys( $this->getLanguageMap() );
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
			$this->languageMap = $this->languageNameUtils->getLanguageNames( 'en', $this->languageNameUtilsInclude );
		}

		return $this->languageMap;
	}

}
