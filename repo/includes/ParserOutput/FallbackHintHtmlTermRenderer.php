<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\FallbackHtmlIndicator;
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
	 * @var FallbackHtmlIndicator
	 */
	private $fallbackHtmlIndicator;

	/**
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct( LanguageNameLookup $languageNameLookup ) {
		$this->fallbackHtmlIndicator = new FallbackHtmlIndicator( $languageNameLookup );
	}

	/**
	 * @param Term $term
	 * @return string HTML
	 */
	public function renderTerm( Term $term ) {
		$html = htmlspecialchars( $term->getText() );
		if ( $term instanceof TermFallback ) {
			$html .= $this->fallbackHtmlIndicator->getHtml( $term );
		}
		return $html;
	}

}
