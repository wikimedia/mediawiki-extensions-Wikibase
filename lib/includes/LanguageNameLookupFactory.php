<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use Language;
use MediaWiki\Languages\LanguageNameUtils;

/**
 * @license GPL-2.0-or-later
 */
class LanguageNameLookupFactory {

	public function getForLanguage( Language $inLanguage ): LanguageNameLookup {
		return $this->getForLanguageCode( $inLanguage->getCode() );
	}

	public function getForLanguageCode( string $inLanguage ): LanguageNameLookup {
		return new LanguageNameLookup( $inLanguage );
	}

	public function getForAutonyms(): LanguageNameLookup {
		return new LanguageNameLookup( LanguageNameUtils::AUTONYMS );
	}
}
