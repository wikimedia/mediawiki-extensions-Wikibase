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
class FallbackHtmlIndicator {

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

		// FIXME: TermFallback should either return equal values or null
		$sourceLanguage = $sourceLanguage === null ? $actualLanguage : $sourceLanguage;

		$isInRequestedLanguage = $actualLanguage === $requestedLanguage;
		$isInSourceLanguage = $actualLanguage === $sourceLanguage;

		if ( $isInRequestedLanguage && $isInSourceLanguage ) {
			// This is neither a fallback nor a transliteration
			return '';
		}

		$sourceLanguageName = $this->languageNameLookup->getName( $sourceLanguage );
		$actualLanguageName = $this->languageNameLookup->getName( $actualLanguage );

		// Generate indicator text
		if ( $isInSourceLanguage ) {
			$text = $sourceLanguageName;
		} else {
			$text = wfMessage(
				'wikibase-language-fallback-transliteration-hint',
				$sourceLanguageName,
				$actualLanguageName
			)->text();
		}

		// Generate HTML class names
		$classes = 'wb-language-fallback-indicator';
		if ( !$isInSourceLanguage ) {
			$classes .= ' wb-language-fallback-transliteration';
		}
		if ( !$isInRequestedLanguage
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
