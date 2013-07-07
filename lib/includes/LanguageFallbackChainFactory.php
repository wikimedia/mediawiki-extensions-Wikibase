<?php

namespace Wikibase;
use Language, IContextSource, MWException;

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
	 * Get the fallback chain based a single language code, and specified fallback level.
	 *
	 * @param string $language
	 * @param $mode bitfield of self::FALLBACK_*
	 *
	 * @return LanguageFallbackChain
	 */
	public function newFromLanguageCode( $languageCode, $mode = self::FALLBACK_ALL ) {

		$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );

		if ( isset( $this->languageCache[$languageCode][$mode] ) ) {
			return $this->languageCache[$languageCode][$mode];
		}

		$chain = $this->buildFromLanguage( $languageCode, $mode );
		$languageFallbackChain = new LanguageFallbackChain( $chain );

		$this->languageCache[$languageCode][$mode] = $languageFallbackChain;

		return $languageFallbackChain;
	}

	/**
	 * Build fallback chain array for a given language or validated language code.
	 *
	 * @param $language Language object or language code as string
	 * @param $mode bitfield of self::FALLBACK_*
	 * @param LanguageFallbackChain[] $chain for recursive calls
	 * @param array $fetched for recursive calls
	 *
	 * @return LanguageWithConversion[]
	 */
	public function buildFromLanguage( $language, $mode, &$chain = array(), &$fetched = array() ) {
		wfProfileIn( __METHOD__ );

		if ( is_string( $language ) ) {
			$languageCode = $language;
		} else {
			$languageCode = $language->getCode();
		}

		if ( $mode & self::FALLBACK_SELF ) {
			if ( !isset( $fetched[$languageCode] ) ) {
				$chain[] = LanguageWithConversion::factory( $language );
				$fetched[$languageCode] = true;
			}
		}

		if ( $mode & self::FALLBACK_VARIANTS ) {
			if ( is_string( $language ) ) {
				$language = Language::factory( $language );
			}
			$parentLanguage = $language->getParentLanguage();
			if ( $parentLanguage ) {
				// It's less likely to trigger conversion mistakes by converting
				// zh-tw to zh-hk first instead of converting zh-cn to zh-tw.
				$variantFallbacks = $parentLanguage->getConverter()
					->getVariantFallbacks( $languageCode );
				if ( is_array( $variantFallbacks ) ) {
					$variants = array_unique( array_merge(
						$variantFallbacks, $parentLanguage->getVariants()
					) );
				} else {
					$variants = $parentLanguage->getVariants();
				}

				foreach ( $variants as $variant ) {
					if ( isset( $fetched[$variant] ) ) {
						continue;
					}

					$chain[] = LanguageWithConversion::factory( $language, $variant );
					$fetched[$variant] = true;
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
			foreach ( Language::getFallbacksFor( $languageCode ) as $other ) {
				$this->buildFromLanguage( $other, $recursiveMode, $chain, $fetched );
			}
		}

		wfProfileOut( __METHOD__ );
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
		wfProfileIn( __METHOD__ );

		$user = $context->getUser();

		if ( !class_exists( 'Babel' ) || $user->isAnon() ) {
			$cached =  $this->newFromLanguage( $context->getLanguage(), self::FALLBACK_ALL );
			wfProfileOut( __METHOD__ );
			return $cached;
		}

		if ( isset( $this->userLanguageCache[$user->getName()][$context->getLanguage()->getCode()] ) ) {
			$cached = $this->userLanguageCache[$user->getName()][$context->getLanguage()->getCode()];
			wfProfileOut( __METHOD__ );
			return $cached;
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

		wfProfileOut( __METHOD__ );
		return $languageFallbackChain;
	}

	/**
	 * Build fallback chain array for a given babel array.
	 *
	 * @param array $babel
	 *
	 * @return LanguageWithConversion[]
	 */
	public function buildFromBabel( array $babel ) {
		wfProfileIn( __METHOD__ );

		$chain = array();
		$fetched = array();

		// First pass to get "compatible" languages (self and variants)
		foreach ( $babel as $languageCodes ) { // Already sorted when added
			foreach ( array( self::FALLBACK_SELF, self::FALLBACK_VARIANTS ) as $mode ) {
				foreach ( $languageCodes as $languageCode ) {
					try {
						$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );
					} catch ( MWException $e ) {
						continue;
					}
					$this->buildFromLanguage( $languageCode, $mode, $chain, $fetched );
				}
			}
		}

		// Second pass to get other languages from system fallback chain
		foreach ( $babel as $languageCodes ) {
			foreach ( $languageCodes as $languageCode ) {
				try {
					$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );
				} catch ( MWException $e ) {
				}
				$this->buildFromLanguage( $languageCode,
					self::FALLBACK_OTHERS | self::FALLBACK_VARIANTS, $chain, $fetched
				);
			}
		}

		wfProfileOut( __METHOD__ );
		return $chain;
	}

}
