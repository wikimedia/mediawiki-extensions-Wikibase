<?php

namespace Wikibase\Repo\ParserOutput\PlaceholderExpander;

use Wikibase\Repo\ParserOutput\TermboxView;

/**
 * @license GPL-2.0-or-later
 */
class ExternallyRenderedEntityViewPlaceholderExpander implements PlaceholderExpander {

	// render the root element and give client side re-rendering a chance
	/* public */ const FALLBACK_HTML = '<div class="wikibase-entitytermsview renderer-fallback"></div>';

	private $htmlBlob;

	/**
	 * @param string|null $htmlBlob
	 */
	public function __construct( $htmlBlob ) {
		$this->htmlBlob = $htmlBlob;
	}

	public function getHtmlForPlaceholder( $name ) {
		if ( $name === TermboxView::TERMBOX_PLACEHOLDER ) {
			return $this->htmlBlob ?: self::FALLBACK_HTML;
		}

		throw new \RuntimeException( "Unknown placeholder: $name" );
	}

}
