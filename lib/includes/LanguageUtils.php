<?php

namespace Wikibase;
use Language, IContextSource;

/**
 * Language-related utility functions for Wikibase.
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
final class LanguageUtils {

	const FALLBACK_ALL = -1;
	const FALLBACK_SELF = 1;
	const FALLBACK_VARIANTS = 2;
	const FALLBACK_OTHERS = 4;

	/**
	 * Returns the fallback chain for a single language.
	 *
	 * @param Language $language
	 * @param $mode bitfield of self::FALLBACK_*
	 *
	 * @return array of LanguageWithConversion objects
	 */
	public static function getFallbackChain( Language $language, $mode = self::FALLBACK_ALL ) {
		static $cache = array();

		if ( isset( $cache[$language->getCode()][$mode] ) ) {
			return $cache[$language->getCode()][$mode];
		}

		$chain = self::getFallbackChainInternal( $language, $mode );

		$cache[$language->getCode()][$mode] = $chain;

		return $chain;
	}

	/**
	 * Internal logic for self::getFallbackChain()
	 *
	 * @param Language $language
	 * @param $mode bitfield of self::FALLBACK_*
	 * @param array $fetched language codes (as array keys) used in previously added items, to avoid duplication
	 *
	 * @return array of LanguageWithConversion objects
	 */
	private static function getFallbackChainInternal( Language $language, $mode, &$fetched = array() ) {
		$chain = array();

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
				$chain = array_merge( $chain, self::getFallbackChainInternal(
					Language::factory( $other ), $recursiveMode, $fetched
				) );
			}
		}

		return $chain;
	}

	/**
	 * Returns the fallback chain for a context, currently based on data provided by Extension:Babel.
	 *
	 * @param IContextSource $context
	 *
	 * @return array of LanguageWithConversion objects
	 */
	public static function getFallbackChainFromContext( IContextSource $context ) {
		static $cache = array();
		static $levels = null;
		$user = $context->getUser();

		if ( !class_exists( 'Babel' ) || $user->isAnon() ) {
			return self::getFallbackChain( $context->getLanguage() );
		}

		if ( isset( $cache[$user->getName()] ) ) {
			return $cache[$user->getName()];
		}

		if ( $levels === null ) {
			global $wgBabelCategoryNames;
			$levels = array_keys( $wgBabelCategoryNames );
			rsort( $levels );
		}

		$fetched = array();
		$chain = array();
		$babels = array();
		$contextLanguage = array( $context->getLanguage()->getCode() );

		if ( count( $levels ) ) {
			// A little redundant but it's the only way to get required information with current Babel API.
			$previousLevelBabel = array();
			foreach ( $levels as $level ) {
				// Make the current language at the top of the chain.
				$levelBabel = array_unique( array_merge(
					$contextLanguage, \Babel::getUserLanguages( $user, $level )
				) );
				$babels[$level] = array_diff( $levelBabel, $previousLevelBabel );
				$previousLevelBabel = $levelBabel;
			}
		} else {
			// Just in case
			$babels['N'] = $contextLanguage;
		}

		// First pass to get "compatible" languages (self and variants)
		foreach ( $babels as $languageCodes ) { // Already sorted when added
			foreach ( array( self::FALLBACK_SELF, self::FALLBACK_VARIANTS ) as $mode ) {
				foreach ( $languageCodes as $languageCode ) {
					$chain = array_merge( $chain, self::getFallbackChainInternal(
						Language::factory( $languageCode ), $mode, $fetched
					) );
				}
			}
		}

		// Second pass to get other languages from system fallback chain
		foreach ( $babels as $languageCodes ) {
			foreach ( $languageCodes as $languageCode ) {
				// Special case for English: In Language::getFallbacksFor(), 'en' is always added
				// to every fallback chain, but we want it to be the last one of the merged chain.
				if ( isset( $fetched['en'] ) ) { // User has already explicitly chosen to see English.
					$enWanted = true;
				} else {
					$mwFallbacks = Language::getLocalisationCache()->getItem( $languageCode, 'fallback' );
					$mwFallbacks = array_map( 'trim', explode( ',', $mwFallbacks ) );
					$enWanted = ( $mwFallbacks[count( $mwFallbacks ) - 1] === 'en' );
				}

				$languageChain = self::getFallbackChainInternal(
					Language::factory( $languageCode ),
					self::FALLBACK_OTHERS | self::FALLBACK_VARIANTS,
					$fetched
				);

				if ( !$enWanted ) {
					// The last item in $languageChain must be for English (not converted).
					unset( $fetched['en'] );
					unset( $languageChain[count( $languageChain ) - 1] );
				}

				$chain = array_merge( $chain, $languageChain );
			}
		}

		// Add English back to the end of the chain if it's not already there (indicated by $fetched['en']).
		$chain = array_merge( $chain, self::getFallbackChainInternal(
			Language::factory( 'en' ), self::FALLBACK_ALL, $fetched
		) );

		$cache[$user->getName()] = $chain;

		return $chain;
	}

}
