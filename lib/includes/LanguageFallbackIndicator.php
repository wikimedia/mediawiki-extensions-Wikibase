<?php

namespace Wikibase\Lib;

use Html;
use Wikibase\DataModel\Term\TermFallback;

/**
 * Generates HTML (usually a 'sup' element) to make the actual and source languages of terms
 * (typically labels and descriptions) that are the result of a language fallback chain and/or
 * transliteration visible to the user.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 */
class LanguageFallbackIndicator {

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	public function __construct( LanguageNameLookup $languageNameLookup ) {
		$this->languageNameLookup = $languageNameLookup;
	}

	public function getHtml( TermFallback $term ) {
		$requestedLanguage = $term->getLanguageCode();
		$actualLanguage = $term->getActualLanguageCode();
		$sourceLanguage = $term->getSourceLanguageCode();

		$isFallback = $actualLanguage !== $requestedLanguage;
		$isTransliteration = $sourceLanguage !== null && $sourceLanguage !== $actualLanguage;

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

		/*
		 * Add a no-break space to separate the indicator from the preceding text,
		 * both visually and for the purpose of double-click selection (T256857).
		 * Note that the preceding text and the indicator may have different directionality (T89047);
		 * for this to look correct (i.e. the space is visually between preceding text and indicator),
		 * the space must not have its directionality overridden –
		 * if we ever add lang= and dir= to the indicator, that must be on a separate element *after* the space.
		 * The space must be part of the outermost indicator element (instead of outside it)
		 * because the indicator may be hidden based on the -variant or -mul classes,
		 * in which case the space should also be hidden (T291608).
		 */
		$text = "\u{00A0}" . $text;

		$classes = 'wb-language-fallback-indicator';
		if ( $isTransliteration ) {
			$classes .= ' wb-language-fallback-transliteration';
		}
		if ( $isFallback
			&& $this->getBaseLanguage( $actualLanguage ) === $this->getBaseLanguage( $requestedLanguage )
		) {
			$classes .= ' wb-language-fallback-variant';
		}
		if ( $isFallback && $actualLanguage === 'mul' ) {
			$classes .= ' wb-language-fallback-mul';
		}

		$attributes = [ 'class' => $classes ];

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
