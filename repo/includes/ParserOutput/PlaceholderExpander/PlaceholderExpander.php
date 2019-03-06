<?php

namespace Wikibase\Repo\ParserOutput\PlaceholderExpander;

/**
 * @license GPL-2.0-or-later
 */
interface PlaceholderExpander {

	/**
	 * @param string $placeholderName
	 *
	 * @return string html
	 */
	public function getHtmlForPlaceholder( $placeholderName );

}
