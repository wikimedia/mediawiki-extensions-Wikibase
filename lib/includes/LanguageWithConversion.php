<?php

namespace Wikibase\Lib;

use Language;
use MediaWiki\MediaWikiServices;
use MWException;

/**
 * Object representing either a verbatim language or a converted language.
 * Used for items in language fallback chain.
 *
 * @license GPL-2.0-or-later
 * @author Liangent < liangent@gmail.com >
 */
class LanguageWithConversion {

	/**
	 * @var array[]
	 */
	private static $objectCache = [];

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var string|null
	 */
	private $sourceLanguageCode;

	/**
	 * @var Language|null
	 */
	private $parentLanguage;

	/**
	 * @var string[]
	 */
	private $translateCache = [];

	/**
	 * @var bool[]
	 */
	private $translatePool = [];

	/**
	 * @param string $languageCode
	 * @param null|string $sourceLanguageCode
	 * @param null|Language $parentLanguage
	 */
	private function __construct(
		$languageCode,
		$sourceLanguageCode = null,
		Language $parentLanguage = null
	) {
		$this->languageCode = $languageCode;
		$this->sourceLanguageCode = $sourceLanguageCode;
		$this->parentLanguage = $parentLanguage;
	}

	/**
	 * Validate a language code. Logic taken from class Language.
	 *
	 * @param string $code Language code
	 *
	 * @return string Validated and normalized code.
	 * @throws MWException on invalid code
	 */
	public static function validateLanguageCode( $code ) {
		global $wgDummyLanguageCodes;

		if ( isset( $wgDummyLanguageCodes[$code] ) ) {
			$code = $wgDummyLanguageCodes[$code];
		}

		if ( !MediaWikiServices::getInstance()->getLanguageNameUtils()->isValidCode( $code )
			|| strcspn( $code, ":/\\\000" ) !== strlen( $code )
		) {
			throw new MWException( __METHOD__ . ': invalid language code ' . $code );
		}

		return $code;
	}

	/**
	 * Get a LanguageWithConversion object.
	 *
	 * @param Language|string $language Language (code) for this object
	 * @param Language|string|null $sourceLanguage
	 *          Source language (code) if this is a converted language, or null
	 *
	 * @throws MWException
	 * @return self
	 */
	public static function factory( $language, $sourceLanguage = null ) {
		if ( is_string( $language ) ) {
			$languageCode = self::validateLanguageCode( $language );
		} else {
			$languageCode = $language->getCode();
		}

		if ( is_string( $sourceLanguage ) ) {
			$sourceLanguageCode = self::validateLanguageCode( $sourceLanguage );
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
			$services = MediaWikiServices::getInstance();
			$langFactory = $services->getLanguageFactory();
			$langConvFactory = $services->getLanguageConverterFactory();
			$parentLanguage = $langFactory->getParentLanguage( $languageCode );

			if ( !$parentLanguage ) {
				throw new MWException( __METHOD__ . ': $language does not support conversion' );
			}

			if ( !$langConvFactory->getLanguageConverter( $parentLanguage )->hasVariant( $sourceLanguageCode ) ) {
				throw new MWException( __METHOD__ . ': given languages do not have the same parent language' );
			}
		} else {
			$parentLanguage = null;
		}

		$object = new self( $languageCode, $sourceLanguageCode, $parentLanguage );
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
	 * Get the code of the source language defined.
	 *
	 * @return string|null
	 */
	public function getSourceLanguageCode() {
		return $this->sourceLanguageCode;
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
	 * Translate data after fetching them.
	 *
	 * @param string $text Data to transform
	 * @return string
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
	 * @param string $text
	 */
	private function prepareForTranslate( $text ) {
		if ( $this->parentLanguage ) {
			$this->translatePool[$text] = true;
		}
	}

	/**
	 * Really execute translation.
	 */
	private function executeTranslate() {
		if ( $this->parentLanguage && count( $this->translatePool ) ) {
			$pieces = array_keys( $this->translatePool );
			$block = implode( "\0", $pieces );
			$translatedBlock = MediaWikiServices::getInstance()->getLanguageConverterFactory()
				->getLanguageConverter( $this->parentLanguage )
				->translate( $block, $this->languageCode );
			$translatedPieces = explode( "\0", $translatedBlock );
			$this->translateCache += array_combine( $pieces, $translatedPieces );
			$this->translatePool = [];
		}
	}

}
