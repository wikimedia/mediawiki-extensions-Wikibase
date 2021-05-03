<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use Language;

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

}
