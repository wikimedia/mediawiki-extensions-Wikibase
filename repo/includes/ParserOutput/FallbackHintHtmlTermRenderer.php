<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\HtmlTermRenderer;
use Wikibase\View\LanguageDirectionalityLookup;

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class FallbackHintHtmlTermRenderer implements HtmlTermRenderer {

	/**
	 * @var LanguageFallbackIndicator
	 */
	private $languageFallbackIndicator;

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

	public function __construct(
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LanguageNameLookup $languageNameLookup
	) {
		$this->languageFallbackIndicator = new LanguageFallbackIndicator( $languageNameLookup );
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
	}

	/**
	 * @param Term $term
	 * @return string HTML representing the term; This will be used in an HTML language and directionality context
	 *   that corresponds to $term->getLanguageCode().
	 */
	public function renderTerm( Term $term ) {
		$html = htmlspecialchars( $term->getText() );
		if ( $term instanceof TermFallback ) {
			$actualLanguageCode = $term->getActualLanguageCode();
			if ( $actualLanguageCode !== $term->getLanguageCode() ) {
				$html = '<span ' .
					'lang="' . htmlspecialchars( $actualLanguageCode ) . '" ' .
					'dir="' . ( $this->languageDirectionalityLookup->getDirectionality( $actualLanguageCode ) ?: 'auto' ) . '"' .
				'>' . $html . '</span>';
			}
			$html .= $this->languageFallbackIndicator->getHtml( $term );
		}
		return $html;
	}

}
