<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use MediaWiki\Languages\LanguageNameUtils;

/**
 * Service for looking up language names based on MediaWiki's Language
 * class.
 *
 * Implementation note: wikibase.getLanguageNameByCode.js contains similar functionality in JS.
 *
 * @license GPL-2.0-or-later
 */
class LanguageNameLookup {

	private LanguageNameUtils $languageNameUtils;

	private MessageInLanguageProvider $messageInLanguageProvider;

	/**
	 * @var string|null
	 */
	private $inLanguage;

	/**
	 * @param LanguageNameUtils $languageNameUtils
	 * @param MessageInLanguageProvider $messageInLanguageProvider
	 * @param string|null $inLanguage Language code of the language in which to return the language
	 *  names. Use LanguageNameUtils::AUTONYMS for autonyms (returns each language name in it's own language).
	 */
	public function __construct(
		LanguageNameUtils $languageNameUtils,
		MessageInLanguageProvider $messageInLanguageProvider,
		?string $inLanguage
	) {
		$this->languageNameUtils = $languageNameUtils;
		$this->messageInLanguageProvider = $messageInLanguageProvider;
		if ( $inLanguage !== LanguageNameUtils::AUTONYMS ) {
			$inLanguage = $this->normalize( $inLanguage );
		}
		$this->inLanguage = $inLanguage;
	}

	/**
	 * Get the name of a language in a general context.
	 *
	 * If the language (code) is being used for terms (labels/descriptions/aliases),
	 * use {@link self::getNameForTerms()} instead.
	 * On the other hand, language codes from {@link WikibaseContentLanguages::CONTEXT_MONOLINGUAL_TEXT},
	 * {@link Site::getLanguageCode()}, or other non-term contexts, should use this method.
	 */
	public function getName( string $languageCode ): string {
		$languageCode = $this->normalize( $languageCode );

		$name = $this->languageNameUtils->getLanguageName( $languageCode, $this->inLanguage );

		if ( $name === '' ) {
			return $languageCode;
		}

		return $name;
	}

	/**
	 * Get the name of a language when it is used in a "terms" context (labels/descriptions/aliases).
	 *
	 * Use this method when the language code is related to {@link WikibaseContentLanguages::CONTEXT_TERM}
	 * or {@link \Wikibase\Repo\WikibaseRepo::getTermsLanguages() WikibaseRepo::getTermsLanguages()}
	 * / {@link \Wikibase\Client\WikibaseClient::getTermsLanguages() WikibaseClient::getTermsLanguages()}.
	 *
	 * The 'mul' language code has a special meaning for terms,
	 * and gets a distinct name in this context to communicate this meaning.
	 * For other language codes, this is equivalent to {@link self::getName()}.
	 */
	public function getNameForTerms( string $languageCode ): string {
		// no need to normalize() before checking, 'mul' contains no hyphens/underscores
		if ( $languageCode === 'mul' ) {
			if ( $this->inLanguage === LanguageNameUtils::AUTONYMS ) {
				return 'mul';
			}
			return $this->messageInLanguageProvider
				->msgInLang( 'wikibase-language-name-for-terms-mul', $this->inLanguage )
				->plain();
		}

		return $this->getName( $languageCode );
	}

	private function normalize( string $languageCode ): string {
		return str_replace( '_', '-', $languageCode );
	}

}
