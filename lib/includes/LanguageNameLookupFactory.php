<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use Language;
use MediaWiki\Languages\LanguageNameUtils;

/**
 * @license GPL-2.0-or-later
 */
class LanguageNameLookupFactory {

	private LanguageNameUtils $languageNameUtils;
	private MessageInLanguageProvider $messageInLanguageProvider;

	public function __construct(
		LanguageNameUtils $languageNameUtils,
		MessageInLanguageProvider $messageInLanguageProvider
	) {
		$this->languageNameUtils = $languageNameUtils;
		$this->messageInLanguageProvider = $messageInLanguageProvider;
	}

	public function getForLanguage( Language $inLanguage ): LanguageNameLookup {
		return $this->getForLanguageCode( $inLanguage->getCode() );
	}

	public function getForLanguageCode( string $inLanguage ): LanguageNameLookup {
		return new LanguageNameLookup(
			$this->languageNameUtils,
			$this->messageInLanguageProvider,
			$inLanguage
		);
	}

	public function getForAutonyms(): LanguageNameLookup {
		return new LanguageNameLookup(
			$this->languageNameUtils,
			$this->messageInLanguageProvider,
			LanguageNameUtils::AUTONYMS
		);
	}
}
