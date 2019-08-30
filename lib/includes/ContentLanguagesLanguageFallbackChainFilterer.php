<?php


namespace Wikibase;

use InvalidArgumentException;
use Language;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class ContentLanguagesLanguageFallbackChainFilterer {

	public function getFallbackChain( ContentLanguages $contentLanguages, LanguageFallbackChain $fallbackChain, Language
	$backupContentLanguage
	): LanguageFallbackChain {
		if ( !$contentLanguages->hasLanguage( $backupContentLanguage->getCode() ) ) {
			throw new InvalidArgumentException( 'backupContentLanguage was not a valid ContentLanguage' );
		}
		$filteredChain = array_filter(
			$fallbackChain->getFallbackChain(),
			function ( LanguageWithConversion $language ) use ( $contentLanguages ) {
				return $contentLanguages->hasLanguage( $language->getLanguageCode() );
			}
		);
		if ( count( $filteredChain ) === 0 ) {
			$filteredChain = [ LanguageWithConversion::factory( $backupContentLanguage ) ];
		}
		return new LanguageFallbackChain( $filteredChain );
	}

}
