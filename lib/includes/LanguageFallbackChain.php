<?php

namespace Wikibase;
use Language;

/**
 * Object representing a language fallback chain used in Wikibase.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 */
class LanguageFallbackChain {

	/**
	 * Fallback levels
	 */
	const FALLBACK_ALL = -1;

	/**
	 * The language itself. eg. 'en' for 'en'.
	 */
	const FALLBACK_SELF = 1;

	/**
	 * Other compatible languages that can be translated into the requested language
	 * (and translation is automatically done). eg. 'sr', 'sr-ec' and 'sr-el' for 'sr'.
	 */
	const FALLBACK_VARIANTS = 2;

	/**
	 * All other language from the system fallback chain. eg. 'de' and 'en' for 'de-formal'.
	 */
	const FALLBACK_OTHERS = 4;

	/**
	 * @var LanguageWithConversion[]
	 */
	private $chain = array();

	/**
	 * @var string[] language codes (as array keys) used in previously added items, to check duplication faster
	 */
	private $fetched = array();

	/**
	 * Build the fallback chain from a single language.
	 *
	 * @param Language $language
	 * @param $mode bitfield of self::FALLBACK_*
	 *
	 * @return LanguageFallbackChain
	 */
	public static function newFromLanguage( Language $language, $mode = self::FALLBACK_ALL ) {
		static $cache = array();

		if ( isset( $cache[$language->getCode()][$mode] ) ) {
			return $cache[$language->getCode()][$mode];
		}

		$chain = new self();
		$chain->loadFromLanguage( $language, $mode );

		$cache[$language->getCode()][$mode] = $chain;

		return $chain;
	}

	/**
	 * Load fallback chain for a given language into this object.
	 *
	 * @param Language $language
	 * @param $mode bitfield of self::FALLBACK_*
	 */
	private function loadFromLanguage( Language $language, $mode ) {

		if ( $mode & self::FALLBACK_SELF ) {
			if ( !isset( $this->fetched[$language->getCode()] ) ) {
				$this->chain[] = LanguageWithConversion::factory( $language );
				$this->fetched[$language->getCode()] = true;
			}
		}

		if ( $mode & self::FALLBACK_VARIANTS ) {
			$parentLanguage = $language->getParentLanguage();
			if ( $parentLanguage ) {
				// It's less likely to trigger conversion mistakes by converting
				// zh-tw to zh-hk first instead of converting zh-cn to zh-tw.
				$variantFallbacks = $parentLanguage->getConverter()
					->getVariantFallbacks( $language->getCode() );
				if ( is_array( $variantFallbacks ) ) {
					$variants = array_unique( array_merge(
						$variantFallbacks, $parentLanguage->getVariants()
					) );
				} else {
					$variants = $parentLanguage->getVariants();
				}

				foreach ( $variants as $variant ) {
					$variantLanguage = Language::factory( $variant );
					if ( isset( $this->fetched[$variantLanguage->getCode()] ) ) {
						continue;
					}

					$this->chain[] = LanguageWithConversion::factory( $language, $variantLanguage );
					$this->fetched[$variantLanguage->getCode()] = true;
				}
			}
		}

		if ( $mode & self::FALLBACK_OTHERS ) {
			// Regarding $mode in recursive calls:
			// * self is a must to have the fallback item itself included;
			// * respect the original caller about whether to include variants or not;
			// * others should be excluded as they'll be handled here in loops.
			$recursiveMode = $mode;
			$recursiveMode &= self::FALLBACK_VARIANTS;
			$recursiveMode |= self::FALLBACK_SELF;
			foreach ( $language->getFallbackLanguages() as $other ) {
				$this->loadFromLanguage( Language::factory( $other ), $recursiveMode );
			}
		}
	}

	/**
	 * Get raw fallback chain as an array. Semi-private for testing.
	 *
	 * @return LanguageWithConversion[]
	 */
	public function getFallbackChain() {
		return $this->chain;
	}

	/**
	 * Try to fetch the best value in a multilingual data array.
	 *
	 * @param string[] $data, language code as keys
	 *
	 * @return null|array of three items: array(
	 * 	'value' => finally fetched and translated value
	 * 	'language' => language code of the language which final value is in
	 * 	'source' => language code of the language where the value is fetched
	 * ), or null when no data can be found.
	 */
	public function resolveMultilingualData( $data ) {

		foreach ( $this->chain as $languageWithConversion ) {
			if ( isset( $data[$languageWithConversion->getFetchLanguage()->getCode()] ) ) {
				return array(
					'value' => $languageWithConversion->translate(
						$data[$languageWithConversion->getFetchLanguage()->getCode()]
					),
					'language' => $languageWithConversion->getLanguage()->getCode(),
					'source' => $languageWithConversion->getFetchLanguage()->getCode(),
				);
			}
		}

		return null;
	}
}
