<?php

namespace Wikibase;

use Babel;
use IContextSource;
use InvalidArgumentException;
use Language;
use LanguageConverter;
use MWException;
use User;

/**
 * Object creating LanguageFallbackChain objects in Wikibase.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Liangent < liangent@gmail.com >
 */
class LanguageFallbackChainFactory {

	/**
	 * Fallback levels
	 */
	const FALLBACK_ALL = 0xff;

	/**
	 * The language itself, e.g. 'en' for 'en'.
	 */
	const FALLBACK_SELF = 1;

	/**
	 * Other compatible languages that can be translated into the requested language
	 * (and translation is automatically done), e.g. 'sr', 'sr-cyrl' and 'sr-latn' for 'sr'.
	 */
	const FALLBACK_VARIANTS = 2;

	/**
	 * All other language from the system fallback chain, e.g. 'de' and 'en' for 'de-formal'.
	 */
	const FALLBACK_OTHERS = 4;

	/**
	 * @var array[]
	 */
	private $languageCache;

	/**
	 * @var array[]
	 */
	private $userLanguageCache;

	/**
	 * @var bool
	 */
	private $anonymousPageViewCached;

	/**
	 * @param bool $anonymousPageViewCached Whether full page outputs are cached for anons, so some
	 *                                      fine-grained fallbacks shouldn't be used for them.
	 */
	public function __construct( $anonymousPageViewCached = false ) {
		// @fixme fix instantiation of factory in various lib classes
		$this->anonymousPageViewCached = $anonymousPageViewCached;
	}

	/**
	 * Get the fallback chain based a single language, and specified fallback level.
	 *
	 * @param Language $language
	 * @param int $mode Bitfield of self::FALLBACK_*
	 *
	 * @return LanguageFallbackChain
	 */
	public function newFromLanguage( Language $language, $mode = self::FALLBACK_ALL ) {
		$languageCode = $language->getCode();

		if ( !isset( $this->languageCache[$languageCode][$mode] ) ) {
			$chain = $this->buildFromLanguage( $language, $mode );
			$this->languageCache[$languageCode][$mode] = new LanguageFallbackChain( $chain );
		}

		return $this->languageCache[$languageCode][$mode];
	}

	/**
	 * Get the fallback chain based a single language code, and specified fallback level.
	 *
	 * @param string $languageCode
	 * @param int $mode Bitfield of self::FALLBACK_*
	 *
	 * @return LanguageFallbackChain
	 */
	public function newFromLanguageCode( $languageCode, $mode = self::FALLBACK_ALL ) {
		$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );

		if ( !isset( $this->languageCache[$languageCode][$mode] ) ) {
			$chain = $this->buildFromLanguage( $languageCode, $mode );
			$this->languageCache[$languageCode][$mode] = new LanguageFallbackChain( $chain );
		}

		return $this->languageCache[$languageCode][$mode];
	}

	/**
	 * Build fallback chain array for a given language or validated language code.
	 *
	 * @param Language|string $language Language object or language code as string
	 * @param int $mode Bitfield of self::FALLBACK_*
	 * @param LanguageFallbackChain[] $chain for recursive calls
	 * @param array $fetched for recursive calls
	 *
	 * @throws InvalidArgumentException
	 * @return LanguageWithConversion[]
	 */
	private function buildFromLanguage( $language, $mode, array &$chain = array(), array &$fetched = array() ) {
		if ( !is_int( $mode ) ) {
			throw new InvalidArgumentException( '$mode must be an integer' );
		}

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
			/** @var Language $parentLanguage */
			$pieces = explode( '-', $languageCode );
			if ( !in_array( $pieces[0], LanguageConverter::$languagesWithVariants ) ) {
				$parentLanguage = null;
			} else {
				if ( is_string( $language ) ) {
					$language = Language::factory( $language );
				}
				$parentLanguage = $language->getParentLanguage();
			}
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
					if ( isset( $fetched[$variant] ) || !$parentLanguage->hasVariant( $variant ) ) {
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

		return $chain;
	}

	/**
	 * Construct the fallback chain based on a context. Currently it just uses user and language info in it.
	 *
	 * @param IContextSource $context
	 *
	 * @return LanguageFallbackChain
	 */
	public function newFromContext( IContextSource $context ) {
		return $this->newFromUserAndLanguageCode( $context->getUser(), $context->getLanguage()->getCode() );
	}

	/**
	 * Construct the fallback chain based on a context, but ignore the language info in it and use a specified one instead.
	 *
	 * @param IContextSource $context
	 * @param string $languageCode
	 *
	 * @return LanguageFallbackChain
	 */
	public function newFromContextAndLanguageCode( IContextSource $context, $languageCode ) {
		return $this->newFromUserAndLanguageCode( $context->getUser(), $languageCode );
	}

	/**
	 * Construct the fallback chain based on a user and a language, currently from data provided by Extension:Babel.
	 *
	 * @param User $user
	 * @param string $languageCode
	 *
	 * @return LanguageFallbackChain
	 */
	public function newFromUserAndLanguageCode( User $user, $languageCode ) {
		if ( !class_exists( Babel::class ) || $user->isAnon() ) {
			return $this->newFromLanguageCode( $languageCode, self::FALLBACK_ALL );
		}

		$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );

		if ( isset( $this->userLanguageCache[$user->getName()][$languageCode] ) ) {
			return $this->userLanguageCache[$user->getName()][$languageCode];
		}

		$babel = $this->getBabel( $languageCode, $user );

		$chain = $this->buildFromBabel( $babel );
		$languageFallbackChain = new LanguageFallbackChain( $chain );

		$this->userLanguageCache[$user->getName()][$languageCode] = $languageFallbackChain;

		return $languageFallbackChain;
	}

	private function getBabel( $languageCode, $user ) {
		$babel = array();

		$babelCategoryNames = $this->getBabelCategoryNames();

		if ( count( $babelCategoryNames ) ) {
			// A little redundant but it's the only way to get required information with current Babel API.
			$previousLevelBabel = array();

			foreach ( $babelCategoryNames as $level => $_ ) {
				// Make the current language at the top of the chain.
				$levelBabel = array_unique( array_merge(
					array( $languageCode ),
					Babel::getUserLanguages( $user, $level )
				) );

				$babel[$level] = array_diff( $levelBabel, $previousLevelBabel );
				$previousLevelBabel = $levelBabel;
			}
		} else {
			$babel['N'] = array( $languageCode );
		}

		return $babel;
	}

	private function getBabelCategoryNames() {
		global $wgBabelCategoryNames;

		$babelCategoryNames = array_filter(
			$wgBabelCategoryNames,
			function( $category ) {
				return $category !== false;
			}
		);

		krsort( $babelCategoryNames );

		return $babelCategoryNames;
	}

	/**
	 * Build fallback chain array for a given babel array.
	 *
	 * @param array $babel
	 *
	 * @return LanguageWithConversion[]
	 */
	public function buildFromBabel( array $babel ) {
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
					continue;
				}
				$this->buildFromLanguage(
					$languageCode,
					self::FALLBACK_OTHERS | self::FALLBACK_VARIANTS,
					$chain,
					$fetched
				);
			}
		}

		return $chain;
	}

}
