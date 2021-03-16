<?php

namespace Wikibase\Lib;

use Babel;
use ExtensionRegistry;
use IContextSource;
use InvalidArgumentException;
use Language;
use LanguageConverter;
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

	/**
	 * Fallback levels
	 */
	public const FALLBACK_ALL = 0xff;

	/**
	 * The language itself, e.g. 'en' for 'en'.
	 */
	public const FALLBACK_SELF = 1;

	/**
	 * Other compatible languages that can be translated into the requested language
	 * (and translation is automatically done), e.g. 'sr', 'sr-ec' and 'sr-el' for 'sr'.
	 */
	public const FALLBACK_VARIANTS = 2;

	/**
	 * All other language from the system fallback chain, e.g. 'de' and 'en' for 'de-formal'.
	 */
	public const FALLBACK_OTHERS = 4;

	/** @var LanguageFactory */
	private $languageFactory;

	/** @var LanguageConverterFactory */
	private $languageConverterFactory;

	/** @var LanguageFallback */
	private $languageFallback;

	/**
	 * @var array[]
	 */
	private $languageCache = [];

	/**
	 * @var array[]
	 */
	private $userLanguageCache = [];

	public function __construct(
		?LanguageFactory $languageFactory = null,
		?LanguageConverterFactory $languageConverterFactory = null,
		?LanguageFallback $languageFallback = null
	) {
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
	 * Get the fallback chain based a single language, and specified fallback level.
	 *
	 * @param Language $language
	 * @param int $mode Bitfield of self::FALLBACK_*
	 *
	 * @return TermLanguageFallbackChain
	 */
	public function newFromLanguage( Language $language, $mode = self::FALLBACK_ALL ) {
		$languageCode = $language->getCode();

		if ( !isset( $this->languageCache[$languageCode][$mode] ) ) {
			$chain = $this->buildFromLanguage( $language, $mode );
			$this->languageCache[$languageCode][$mode] = new TermLanguageFallbackChain(
				$chain,
				WikibaseContentLanguages::getDefaultInstance()->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
			);
		}

		return $this->languageCache[$languageCode][$mode];
	}

	/**
	 * Get the fallback chain based a single language code, and specified fallback level.
	 *
	 * @param string $languageCode
	 * @param int $mode Bitfield of self::FALLBACK_*
	 *
	 * @return TermLanguageFallbackChain
	 */
	public function newFromLanguageCode( $languageCode, $mode = self::FALLBACK_ALL ) {
		$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );

		if ( !isset( $this->languageCache[$languageCode][$mode] ) ) {
			$chain = $this->buildFromLanguage( $languageCode, $mode );
			$this->languageCache[$languageCode][$mode] = new TermLanguageFallbackChain(
				$chain,
				WikibaseContentLanguages::getDefaultInstance()->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
			);
		}

		return $this->languageCache[$languageCode][$mode];
	}

	/**
	 * Build fallback chain array for a given language or validated language code.
	 *
	 * @param Language|string $language Language object or language code as string
	 * @param int $mode Bitfield of self::FALLBACK_*
	 * @param TermLanguageFallbackChain[] $chain for recursive calls
	 * @param bool[] $fetched for recursive calls
	 *
	 * @throws InvalidArgumentException
	 * @return LanguageWithConversion[]
	 */
	private function buildFromLanguage( $language, $mode, array $chain = [], array &$fetched = [] ) {
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
			$parentLanguage = null;
			$pieces = explode( '-', $languageCode, 2 );

			if ( in_array( $pieces[0], LanguageConverter::$languagesWithVariants ) ) {
				if ( is_string( $language ) ) {
					$language = $this->languageFactory->getLanguage( $language );
				}
				$parentLanguage = $this->languageFactory->getParentLanguage( $language->getCode() );
			}

			if ( $parentLanguage ) {
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
						// The self::FALLBACK_SELF mode is already responsible for self-references.
						&& $variant !== $languageCode
						&& $parentLanguageConverter->hasVariant( $variant )
					) {
						$chain[] = LanguageWithConversion::factory( $language, $variant );
						$fetched[$variant] = true;
					}
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

			$fallbacks = $this->languageFallback->getAll( $languageCode );
			foreach ( $fallbacks as $other ) {
				$chain = $this->buildFromLanguage( $other, $recursiveMode, $chain, $fetched );
			}
		}

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
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Babel' ) || $user->isAnon() ) {
			return $this->newFromLanguageCode( $languageCode, self::FALLBACK_ALL );
		}

		$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );

		if ( isset( $this->userLanguageCache[$user->getName()][$languageCode] ) ) {
			return $this->userLanguageCache[$user->getName()][$languageCode];
		}

		$babel = $this->getBabel( $languageCode, $user );

		$chain = $this->buildFromBabel( $babel );
		$languageFallbackChain = new TermLanguageFallbackChain(
			$chain,
			WikibaseContentLanguages::getDefaultInstance()->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
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

		// First pass to get "compatible" languages (self and variants)
		foreach ( $babel as $languageCodes ) { // Already sorted when added
			foreach ( [ self::FALLBACK_SELF, self::FALLBACK_VARIANTS ] as $mode ) {
				foreach ( $languageCodes as $languageCode ) {
					try {
						$languageCode = LanguageWithConversion::validateLanguageCode( $languageCode );
					} catch ( MWException $e ) {
						continue;
					}
					$chain = $this->buildFromLanguage( $languageCode, $mode, $chain, $fetched );
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
				$chain = $this->buildFromLanguage(
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
