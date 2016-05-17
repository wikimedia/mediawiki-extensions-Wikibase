<?php

namespace Wikibase\Lib;

use Html;
use Wikibase\DataModel\Term\TermFallback;

/**
 * Formats entity IDs by generating an HTML link to the corresponding page title.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Mättig
 */
class LanguageFallbackIndicator {

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct( LanguageNameLookup $languageNameLookup ) {
		$this->languageNameLookup = $languageNameLookup;
	}

	public function getHtml( TermFallback $term ) {
		$requestedLanguage = $term->getLanguageCode();
		$actualLanguage = $term->getActualLanguageCode();
		$sourceLanguage = $term->getSourceLanguageCode();

		$isFallback = $actualLanguage !== $requestedLanguage;
		$isTransliteration = $sourceLanguage === null || $sourceLanguage !== $actualLanguage;

		if ( !$isFallback && !$isTransliteration ) {
			return '';
		}

		$text = $this->languageNameLookup->getName( $actualLanguage );

		if ( $isTransliteration ) {
			$text = wfMessage(
				'wikibase-language-fallback-transliteration-hint',
				$this->languageNameLookup->getName( $sourceLanguage ),
				$text
			)->text();
		}

		$classes = 'wb-language-fallback-indicator';
		if ( $isTransliteration ) {
			$classes .= ' wb-language-fallback-transliteration';
		}
		if ( $isFallback
			&& $this->getBaseLanguage( $actualLanguage ) === $this->getBaseLanguage( $requestedLanguage )
		) {
			$classes .= ' wb-language-fallback-variant';
		}

		$attributes = array( 'class' => $classes );

		$html = Html::element( 'sup', $attributes, $text );
		return $html;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function getBaseLanguage( $languageCode ) {
		return preg_replace( '/-.*/', '', $languageCode );
	}

}
