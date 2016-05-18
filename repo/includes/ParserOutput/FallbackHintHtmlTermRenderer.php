<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\HtmlTermRenderer;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class FallbackHintHtmlTermRenderer implements HtmlTermRenderer {

	/**
	 * @var LanguageFallbackIndicator
	 */
	private $languageFallbackIndicator;

	/**
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct( LanguageNameLookup $languageNameLookup ) {
		$this->languageFallbackIndicator = new LanguageFallbackIndicator( $languageNameLookup );
	}

	/**
	 * @param Term $term
	 * @return string HTML
	 */
	public function renderTerm( Term $term ) {
		$html = htmlspecialchars( $term->getText() );
		if ( $term instanceof TermFallback ) {
			$html .= $this->languageFallbackIndicator->getHtml( $term );
		}
		return $html;
	}

}
