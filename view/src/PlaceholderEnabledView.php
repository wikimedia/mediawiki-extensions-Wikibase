<?php

namespace Wikibase\View;

/**
 * A view containing rendered HTML that may still contain placeholders to be replaced
 * before the HTML can be presented to the end-user.
 * The placeholders can e.g. be stored in the (cached) ParserOutput object for later
 * use in the page output.
 * @see \ParserOutput::setExtensionData()
 * @see \Wikibase\RepoHooks::onOutputPageParserOutput()
 *
 * @license GPL-2.0-or-later
 */
class PlaceholderEnabledView {

	/**
	 * @var string HTML
	 */
	private $html;

	/**
	 * @var array
	 */
	private $placeholders;

	public function __construct( $html, array $placeholders ) {
		$this->html = $html;
		$this->placeholders = $placeholders;
	}

	public function getHtml() {
		return $this->html;
	}

	public function getPlaceholders() {
		return $this->placeholders;
	}

}
