<?php

namespace Wikibase;
use \MWException;
use \Language;

/**
 * Object representing either a verbatim language or a converted language.
 * Used for items in language fallback chain.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 */
class LanguageWithConversion {

	static protected $objectCache = array();

	protected $language;
	protected $languageCode;
	protected $sourceLanguage;
	protected $sourceLanguageCode;
	protected $parentLanguage;

	protected $translateCache = array();
	protected $translatePool = array();

	/**
	 * Constructor.
	 *
	 * @param null|Language $language
	 * @param string $languageCode
	 * @param null|Language $sourceLanguage
	 * @param null|string $sourceLanguageCode
	 * @param null|Language $parentLanguage
	 */
	protected function __construct( $language, $languageCode, $sourceLanguage, $sourceLanguageCode, $parentLanguage ) {
		$this->language = $language;
		$this->languageCode = $languageCode;
		$this->sourceLanguage = $sourceLanguage;
		$this->sourceLanguageCode = $sourceLanguageCode;
		$this->parentLanguage = $parentLanguage;
	}

	/**
	 * Validate a language code. Logic taken from class Language.
	 *
	 * @param string $code Language code
	 *
	 * @return string|null Validated and normalized code, or null on invalid code.
	 */
	public static function validateLanguageCode( $code ) {
		global $wgDummyLanguageCodes;

		if ( isset( $wgDummyLanguageCodes[$code] ) ) {
			$code = $wgDummyLanguageCodes[$code];
		}

		if ( !Language::isValidCode( $code )
			|| strcspn( $code, ":/\\\000" ) !== strlen( $code )
		) {
			return null;
		}

		return $code;
	}

	/**
	 * Get a LanguageWithConversion object.
	 *
	 * @param $language Language|string: Language (code) for this object
	 * @param $sourceLanguage null|Language|string:
	 *          Source language (code) if this is a converted language, or null
	 * @return LanguageWithConversion
	 */
	public static function factory( $language, $sourceLanguage = null ) {
		if ( is_string( $language ) ) {
			$languageCode = self::validateLanguageCode( $language );
			if ( $languageCode === null ) {
				throw new MWException( __METHOD__ . ': invalid language code ' . $language );
			}
			$language = null;
		} else {
			$languageCode = $language->getCode();
		}

		if ( is_string( $sourceLanguage ) ) {
			$sourceLanguageCode = self::validateLanguageCode( $sourceLanguage );
			if ( $sourceLanguageCode === null ) {
				throw new MWException( __METHOD__ . ': invalid source language code ' . $sourceLanguage );
			}
			$sourceLanguage = null;
		} elseif ( $sourceLanguage === null ) {
			$sourceLanguageCode = null;
		} else {
			$sourceLanguageCode = $sourceLanguage->getCode();
		}

		$sourceLanguageKey = $sourceLanguageCode === null ? '' : $sourceLanguageCode;
		if ( isset( self::$objectCache[$languageCode][$sourceLanguageKey] ) ) {
			return self::$objectCache[$languageCode][$sourceLanguageKey];
		}

		if ( $sourceLanguageCode !== null ) {
			if ( !$language ) {
				$language = Language::factory( $languageCode );
			}
			$parentLanguage = $language->getParentLanguage();

			if ( !$parentLanguage ) {
				throw new MWException( __METHOD__ . ': $language does not support conversion' );
			}

			if ( !$parentLanguage->hasVariant( $sourceLanguageCode ) ) {
				throw new MWException( __METHOD__ . ': given languages do not have the same parent language' );
			}
		} else {
			$parentLanguage = null;
		}

		$object = new self( $language, $languageCode, $sourceLanguage, $sourceLanguageCode, $parentLanguage );
		self::$objectCache[$languageCode][$sourceLanguageKey] = $object;
		return $object;
	}

	/**
	 * Get the code of the language this object wraps.
	 *
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * Get the language this object wraps.
	 *
	 * @return Language
	 */
	public function getLanguage() {
		if ( !$this->language ) {
			$this->language = Language::factory( $this->languageCode );
		}

		return $this->language;
	}

	/**
	 * Get the code of the source language defined.
	 *
	 * @return string
	 */
	public function getSourceLanguageCode() {
		return $this->sourceLanguageCode;
	}

	/**
	 * Get the source language defined.
	 *
	 * @return Language
	 */
	public function getSourceLanguage() {
		if ( $this->sourceLanguageCode !== null && !$this->sourceLanguage ) {
			$this->sourceLanguage = Language::factory( $this->sourceLanguageCode );
		}

		return $this->sourceLanguage;
	}

	/**
	 * Get the code of the language where data should be fetched.
	 *
	 * @return string
	 */
	public function getFetchLanguageCode() {
		if ( $this->sourceLanguageCode !== null ) {
			return $this->sourceLanguageCode;
		} else {
			return $this->languageCode;
		}
	}

	/**
	 * Get the language where data should be fetched. Generally this doesn't need to be used.
	 *
	 * @return Language
	 */
	public function getFetchLanguage() {
		if ( $this->sourceLanguageCode !== null ) {
			return $this->getSourceLanguage();
		} else {
			return $this->getLanguage();
		}
	}

	/**
	 * Translate data after fetching them.
	 *
	 * @param $text String: Data to transform
	 * @return String: Result
	 */
	public function translate( $text ) {
		if ( $this->parentLanguage ) {
			if ( isset( $this->translateCache[$text] ) ) {
				return $this->translateCache[$text];
			} else {
				$this->prepareForTranslate( $text );
				$this->executeTranslate();
				return $this->translateCache[$text];
			}
		} else {
			return $text;
		}
	}

	/**
	 * Insert a text snippet which will be translated later.
	 *
	 * Due to the implementation of language converter, massive
	 * calls with short text snippets may introduce big overhead.
	 * If it's foreseeable that some text will be translated
	 * later, add it here for batched translation.
	 *
	 * Does nothing if this is not a converted language.
	 *
	 * @param $text String
	 */
	public function prepareForTranslate( $text ) {
		if ( $this->parentLanguage ) {
			$this->translatePool[$text] = true;
		}
	}

	/**
	 * Really execute translation.
	 */
	protected function executeTranslate() {
		if ( $this->parentLanguage && count( $this->translatePool ) ) {
			$pieces = array_keys( $this->translatePool );
			$block = implode( "\0", $pieces );
			$translatedBlock = $this->parentLanguage->getConverter()->translate(
				$block, $this->language->getCode()
			);
			$translatedPieces = explode( "\0", $translatedBlock );
			$this->translateCache += array_combine( $pieces, $translatedPieces );
			$this->translatePool = array();
		}
	}
}
