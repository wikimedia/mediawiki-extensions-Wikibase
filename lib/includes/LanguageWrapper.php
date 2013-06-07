<?php

namespace Wikibase;
use MWException;

/**
 * Object representing either a basic language or a converted language.
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
class LanguageWrapper {

	static protected $objectCache = array();
	static protected $translateCacheGlobal = array();
	static protected $translatePoolGlobal = array();
	protected $language, $sourceLanguage, $parentLanguage, $translateCache, $translatePool;

	/**
	 * Constructor.
	 *
	 * @param $language Language
	 * @param $sourceLanguage null|Language
	 * @param $parentLanguage null|Language
	 */
	protected function __construct( $language, $sourceLanguage, $parentLanguage ) {
		$this->language = $language;
		$this->sourceLanguage = $sourceLanguage;
		$this->parentLanguage = $parentLanguage;
		if ( $parentLanguage ) {
			if ( !isset( self::$translateCacheGlobal[$parentLanguage->getCode()] ) ) {
				self::$translateCacheGlobal[$parentLanguage->getCode()] = array();
			}
			if ( !isset( self::$translatePoolGlobal[$parentLanguage->getCode()] ) ) {
				self::$translatePoolGlobal[$parentLanguage->getCode()] = array();
			}
			$this->translateCache = &self::$translateCacheGlobal[$parentLanguage->getCode()];
			$this->translatePool = &self::$translatePoolGlobal[$parentLanguage->getCode()];
		}
	}

	/**
	 * Get a LanguageWrapper object.
	 *
	 * @param $language Language: Language for this object
	 * @param $sourceLanguage null|Language:
	 *          Source language if this is a converted language, or null
	 * @return Language
	 */
	public static function factory( $language, $sourceLanguage = null ) {
		$sourceCode = $sourceLanguage ? $sourceLanguage->getCode() : '';
		if ( isset( self::$objectCache[$language->getCode()][$sourceCode] ) ) {
			return self::$objectCache[$language->getCode()][$sourceCode];
		}

		if ( $sourceLanguage ) {
			$parentLanguage = $language->getParentLanguage();
			$sourceParentLanguage = $sourceLanguage->getParentLanguage();
			if ( !$parentLanguage || !$sourceParentLanguage ) {
				throw new MWException( __METHOD__ .
					': either $language or $sourceLanguage does not support conversion.'
				);
			}
			if ( $parentLanguage->getCode() !== $sourceParentLanguage->getCode() ) {
				throw new MWException( __METHOD__ .
					': $language and $sourceLanguage do not share the same parent language'
				);
			}
		} else {
			$parentLanguage = null;
		}

		$object = new self( $language, $sourceLanguage, $parentLanguage );
		self::$objectCache[$language->getCode()][$sourceCode] = $object;
		return $object;
	}

	/**
	 * Get the language this object wraps.
	 *
	 * @return Language
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Get the language where data should be fetched.
	 *
	 * @return Language
	 */
	public function getFetchLanguage() {
		if ( $this->sourceLanguage ) {
			return $this->sourceLanguage;
		} else {
			return $this->language;
		}
	}

	/**
	 * Translate data after fetching them.
	 *
	 * @param $text String: Data to transform
	 * @return String: Results
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
	 * calls with short text snippets introduce big overhead. If
	 * it's foreseeable that some text will be translated later,
	 * add it here for batched translation.
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
