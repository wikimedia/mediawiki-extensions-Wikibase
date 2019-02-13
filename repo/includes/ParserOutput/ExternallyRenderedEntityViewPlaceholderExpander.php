<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\View\Termbox\TermboxView;

/**
 * TODO (EntityView)PlaceholderExpander interface?
 *
 * @license GPL-2.0-or-later
 */
class ExternallyRenderedEntityViewPlaceholderExpander {

	private $htmlBlob;

	/**
	 * @param $string
	 */
	public function __construct( $htmlBlob ) {
		$this->htmlBlob = $htmlBlob;
	}

	public function getHtmlForPlaceholder( $name ) {
		if ( $name === TermboxView::TERMBOX_PLACEHOLDER ) {
			return $this->htmlBlob;
		}

		throw new \RuntimeException( "Unknown placeholder: $name" );
	}

}
