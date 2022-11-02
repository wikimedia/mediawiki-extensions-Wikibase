<?php

namespace Wikibase\Lib;

use ExtensionRegistry;
use IContextSource;
use Language;
use LanguageConverter;
use MediaWiki\Babel\Babel;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Languages\LanguageFallback;
use MediaWiki\MediaWikiServices;
use MWException;
use User;

/**
 * Object creating TermLanguageFallbackChain objects in Wikibase.
 *
 * @license GPL-2.0-or-later
 * @author Liangent < liangent@gmail.com >
 */
class LanguageFallbackChainFactory {

	/** @var ContentLanguages */
	private $termsLanguages;

	/** @var LanguageFactory */
	private $languageFactory;

	/** @var LanguageConverterFactory */
	private $languageConverterFactory;

	/** @var LanguageFallback */
	private $languageFallback;

	/**
	 * @var TermLanguageFallbackChain[]
	 */
	private $languageCache = [];

	/**
	 * @var array[]
	 */
	private $userLanguageCache = [];

	public function __construct(
		?ContentLanguages $termsLanguages = null,
		?LanguageFactory $languageFactory = null,
		?LanguageConverterFactory $languageConverterFactory = null,
		?LanguageFallback $languageFallback = null
	) {
		// note: this is lib code, so we can’t get the default term languages from the repo or client services
		$this->termsLanguages = $termsLanguages ?:
			WikibaseContentLanguages::getDefaultInstance()->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM );
		// note: do not extract MediaWikiServices::getInstance() into a variable –
		// if all three services are given, the service container should never be loaded
		// (important in unit tests, where it isn’t set up)
		$this->languageFactory = $languageFactory ?:
			MediaWikiServices::getInstance()->getLanguageFactory();
		$this->languageConverterFactory = $languageConverterFactory ?:
			MediaWikiServices::getInstance()->getLanguageConverterFactory();
		$this->languageFallback = $languageFallback ?:
			MediaWikiServices::getInstance()->getLanguageFallback();
	}

	/**
	 * Get the fallback chain based a single language.
	 *
	 * @param Language $language
	 *
	 * @return TermLanguageFallbackChain
	 */
	public function newFromLanguage( Language $language ) {
		$languageCode = $language->getCode();

		if ( !isset( $this->languageCache[$languageCode] ) ) {
			$chain = $this->buildFromLanguage( $language );
			$this->languageCache[$languageCode] = new TermLanguageFallbackChain(
				$chain,
				$this->termsLanguages
			);
		}

		return $this->languageCache[$languageCode];
	}

	/**
	 * Get the fallback chain based a single language code.
	 *
	 * @param string $languageCode
	 *
	 * @return TermLanguageFallbackChain
	 */
	public function newFromLanguageCode( $languageCode ) {
		$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );

		if ( !isset( $this->languageCache[$languageCode] ) ) {
			$chain = $this->buildFromLanguage( $languageCode );
			$this->languageCache[$languageCode] = new TermLanguageFallbackChain(
				$chain,
				$this->termsLanguages
			);
		}

		return $this->languageCache[$languageCode];
	}

	/**
	 * Add the given language to the chain (if it’s not included already).
	 *
	 * @param Language|string $language language (code)
	 * @param LanguageWithConversion[] &$chain the resulting chain
	 * @param bool[] &$fetched language codes (as keys) that are already in the chain
	 */
	private function addLanguageToChain( $language, array &$chain, array &$fetched ): void {
		$languageCode = is_string( $language ) ? $language : $language->getCode();
		if ( !isset( $fetched[$languageCode] ) ) {
			$chain[] = LanguageWithConversion::factory( $language );
			$fetched[$languageCode] = true;
		}
	}

	/**
	 * Add the given language and its variants to the chain (if not included already).
	 *
	 * @param Language|string $language language (code)
	 * @param LanguageWithConversion[] &$chain the resulting chain
	 * @param bool[] &$fetched language codes (as keys) that are already in the chain
	 */
	private function addLanguageAndVariantsToChain( $language, array &$chain, array &$fetched ): void {
		$languageCode = is_string( $language ) ? $language : $language->getCode();

		$this->addLanguageToChain( $language, $chain, $fetched );

		$parentLanguage = null;
		$pieces = explode( '-', $languageCode, 2 );

		if ( in_array( $pieces[0], LanguageConverter::$languagesWithVariants ) ) {
			$parentLanguage = $this->languageFactory->getParentLanguage( $languageCode );
		}

		if ( !$parentLanguage ) {
			return;
		}

		// It's less likely to trigger conversion mistakes by converting
		// zh-tw to zh-hk first instead of converting zh-cn to zh-tw.
		$parentLanguageConverter = $this->languageConverterFactory->getLanguageConverter( $parentLanguage );
		$variantFallbacks = $parentLanguageConverter->getVariantFallbacks( $languageCode );
		if ( is_array( $variantFallbacks ) ) {
			$variants = array_unique( array_merge(
				$variantFallbacks,
				$parentLanguageConverter->getVariants()
			) );
		} else {
			$variants = $parentLanguageConverter->getVariants();
		}

		foreach ( $variants as $variant ) {
			if ( !isset( $fetched[$variant] )
				 && $parentLanguageConverter->hasVariant( $variant )
			) {
				$chain[] = LanguageWithConversion::factory( $language, $variant );
				$fetched[$variant] = true;
			}
		}
	}

	/**
	 * Add the given language, its variants and its *explicit* fallbacks to the chain (if not included already).
	 *
	 * For the *implicit* (non-strict) fallbacks, see {@link addImplicitFallbacksToChain}.
	 *
	 * @param Language|string $language language (code)
	 * @param LanguageWithConversion[] &$chain the resulting chain
	 * @param bool[] &$fetched language codes (as keys) that are already in the chain
	 */
	private function addLanguageAndVariantsAndFallbacksToChain( $language, array &$chain, array &$fetched ): void {
		$this->addLanguageAndVariantsToChain( $language, $chain, $fetched );

		$languageCode = is_string( $language ) ? $language : $language->getCode();
		$fallbacks = $this->languageFallback->getAll( $languageCode, LanguageFallback::STRICT );
		foreach ( $fallbacks as $other ) {
			$this->addLanguageAndVariantsToChain( $other, $chain, $fetched );
		}
	}

	/**
	 * Add the *implicit* fallbacks for any language to the chain (if not included already).
	 *
	 * For the *explicit* fallbacks (of a specific language), see {@link addLanguageAndVariantsAndFallbacksToChain}.
	 *
	 * @param LanguageWithConversion[] &$chain the resulting chain
	 * @param bool[] &$fetched language codes (as keys) that are already in the chain
	 */
	private function addImplicitFallbacksToChain( array &$chain, array &$fetched ): void {
		$this->addLanguageToChain( 'mul', $chain, $fetched );
		$this->addLanguageToChain( 'en', $chain, $fetched );
	}

	/**
	 * Build a full fallback chain from the single given language, its variants and fallbacks.
	 *
	 * @param Language|string $language (code)
	 * @return LanguageWithConversion[] the resulting chain
	 */
	private function buildFromLanguage( $language ): array {
		$chain = [];
		$fetched = [];
		$this->addLanguageAndVariantsAndFallbacksToChain( $language, $chain, $fetched );
		$this->addImplicitFallbacksToChain( $chain, $fetched );
		return $chain;
	}

	/**
	 * Construct the fallback chain based on a context. Currently it just uses user and language info in it.
	 *
	 * @param IContextSource $context
	 *
	 * @return TermLanguageFallbackChain
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
	 * @return TermLanguageFallbackChain
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
	 * @return TermLanguageFallbackChain
	 */
	public function newFromUserAndLanguageCode( User $user, $languageCode ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Babel' ) || !$user->isRegistered() ) {
			return $this->newFromLanguageCode( $languageCode );
		}

		$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );

		if ( isset( $this->userLanguageCache[$user->getName()][$languageCode] ) ) {
			return $this->userLanguageCache[$user->getName()][$languageCode];
		}

		$babel = $this->getBabel( $languageCode, $user );

		$chain = $this->buildFromBabel( $babel );
		$languageFallbackChain = new TermLanguageFallbackChain(
			$chain,
			$this->termsLanguages
		);

		$this->userLanguageCache[$user->getName()][$languageCode] = $languageFallbackChain;

		return $languageFallbackChain;
	}

	private function getBabel( $languageCode, $user ) {
		$babel = [];

		$babelCategoryNames = $this->getBabelCategoryNames();

		if ( count( $babelCategoryNames ) ) {
			// A little redundant but it's the only way to get required information with current Babel API.
			$previousLevelBabel = [];

			foreach ( $babelCategoryNames as $level => $_ ) {
				// Make the current language at the top of the chain.
				$levelBabel = array_unique( array_merge(
					[ $languageCode ],
					Babel::getCachedUserLanguages( $user, $level )
				) );

				$babel[$level] = array_diff( $levelBabel, $previousLevelBabel );
				$previousLevelBabel = $levelBabel;
			}
		} else {
			$babel['N'] = [ $languageCode ];
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
		$chain = [];
		$fetched = [];

		// validate all the language codes and discard invalid ones
		$babel = array_map( static function ( $languageCodes ) {
			$validCodes = [];
			foreach ( $languageCodes as $languageCode ) {
				try {
					$validCodes[] = LanguageWithConversion::validateLanguageCode( $languageCode );
				} catch ( MWException $e ) {
					continue;
				}
			}
			return $validCodes;
		}, $babel );

		// First pass to get "compatible" languages (self and variants)
		foreach ( $babel as $languageCodes ) { // Already sorted when added
			foreach ( $languageCodes as $languageCode ) {
				$this->addLanguageToChain( $languageCode, $chain, $fetched );
			}
			foreach ( $languageCodes as $languageCode ) {
				$this->addLanguageAndVariantsToChain( $languageCode, $chain, $fetched );
			}
		}

		// Second pass to get other languages from system fallback chain
		foreach ( $babel as $languageCodes ) {
			foreach ( $languageCodes as $languageCode ) {
				$this->addLanguageAndVariantsAndFallbacksToChain( $languageCode, $chain, $fetched );
			}
		}

		// Third pass to add implicit, language-independent fallbacks
		$this->addImplicitFallbacksToChain( $chain, $fetched );

		return $chain;
	}

}
