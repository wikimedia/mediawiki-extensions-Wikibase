<?php

namespace Wikibase;
use Language, IContextSource;

/**
 * Object creating LanguageFallbackChain objects in Wikibase.
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
class LanguageFallbackChainFactory {

	/**
	 * Fallback levels
	 */
	const FALLBACK_ALL = 0xff;

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
	 * @var array
	 */
	public $languageCache;

	/**
	 * @var array
	 */
	public $userLanguageCache;

	/**
	 * Get the fallback chain based a single language, and specified fallback level.
	 *
	 * @param Language $language
	 * @param $mode bitfield of self::FALLBACK_*
	 *
	 * @return LanguageFallbackChain
	 */
	public function newFromLanguage( Language $language, $mode = self::FALLBACK_ALL ) {

		if ( isset( $this->languageCache[$language->getCode()][$mode] ) ) {
			return $this->languageCache[$language->getCode()][$mode];
		}

		$chain = $this->buildFromLanguage( $language, $mode );
		$languageFallbackChain = new LanguageFallbackChain( $chain );

		$this->languageCache[$language->getCode()][$mode] = $languageFallbackChain;

		return $languageFallbackChain;
	}

	/**
	 * Build fallback chain for a given language.
	 *
	 * @param Language $language
	 * @param $mode bitfield of self::FALLBACK_*
	 * @param LanguageFallbackChain[] $chain for recursive calls
	 * @param array $fetched for recursive calls
	 */
	private function buildFromLanguage( Language $language, $mode, &$chain = array(), &$fetched = array() ) {

		if ( $mode & self::FALLBACK_SELF ) {
			if ( !isset( $fetched[$language->getCode()] ) ) {
				$chain[] = LanguageWithConversion::factory( $language );
				$fetched[$language->getCode()] = true;
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
					if ( isset( $fetched[$variantLanguage->getCode()] ) ) {
						continue;
					}

					$chain[] = LanguageWithConversion::factory( $language, $variantLanguage );
					$fetched[$variantLanguage->getCode()] = true;
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
				$this->buildFromLanguage( Language::factory( $other ), $recursiveMode, $chain, $fetched );
			}
		}

		return $chain;
	}

	/**
	 * Construct the fallback chain based a context, currently from on data provided by Extension:Babel.
	 *
	 * @param IContextSource $context
	 *
	 * @return LanguageFallbackChain
	 */
	public function newFromContext( IContextSource $context ) {
		global $wgBabelCategoryNames;

		$user = $context->getUser();

		if ( !class_exists( 'Babel' ) || $user->isAnon() ) {
			return $this->newFromLanguage( $context->getLanguage(), self::FALLBACK_ALL );
		}

		if ( isset( $this->userLanguageCache[$user->getName()][$context->getLanguage()->getCode()] ) ) {
			return $this->userLanguageCache[$user->getName()][$context->getLanguage()->getCode()];
		}

		$babel = array();
		$contextLanguage = array( $context->getLanguage()->getCode() );

		if ( count( $wgBabelCategoryNames ) ) {
			// A little redundant but it's the only way to get required information with current Babel API.
			$previousLevelBabel = array();
			foreach ( $wgBabelCategoryNames as $level => $_ ) {
				// Make the current language at the top of the chain.
				$levelBabel = array_unique( array_merge(
					$contextLanguage, \Babel::getUserLanguages( $user, $level )
				) );
				$babel[$level] = array_diff( $levelBabel, $previousLevelBabel );
				$previousLevelBabel = $levelBabel;
			}
		} else {
			// Just in case
			$babel['N'] = $contextLanguage;
		}

		$chain = $this->buildFromBabel( $babel );
		$languageFallbackChain = new LanguageFallbackChain( $chain );

		$this->userLanguageCache[$user->getName()][$context->getLanguage()->getCode()] = $languageFallbackChain;

		return $languageFallbackChain;
	}

	/**
	 * Build fallback chain for a given babel array.
	 *
	 * @param array $babel
	 */
	private function buildFromBabel( array $babel ) {

		$chain = array();
		$fetched = array();

		// First pass to get "compatible" languages (self and variants)
		foreach ( $babel as $languageCodes ) { // Already sorted when added
			foreach ( array( self::FALLBACK_SELF, self::FALLBACK_VARIANTS ) as $mode ) {
				foreach ( $languageCodes as $languageCode ) {
					$this->buildFromLanguage( Language::factory( $languageCode ), $mode, $chain, $fetched );
				}
			}
		}

		// Second pass to get other languages from system fallback chain
		foreach ( $babel as $languageCodes ) {
			foreach ( $languageCodes as $languageCode ) {
				$this->buildFromLanguage( Language::factory( $languageCode ),
					self::FALLBACK_OTHERS | self::FALLBACK_VARIANTS, $chain, $fetched
				);
			}
		}

		return $chain;
	}

}
