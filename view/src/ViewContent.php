<?php

namespace Wikibase\View;

/**
 * A view containing rendered HTML that may still contain placeholders to be replaced
 * before the HTML can be presented to the end-user.
 * The placeholders can e.g. be stored in the (cached) ParserOutput object for later
 * use in the page output.
 * @see \ParserOutput::setExtensionData()
 * @see \Wikibase\Repo\RepoHooks::onOutputPageParserOutput()
 *
 * @license GPL-2.0-or-later
 */
class ViewContent {

	/**
	 * @var string HTML
	 */
	private $html;

	/**
	 * @var array
	 */
	private $placeholders;

	public function __construct( $html, array $placeholders = [] ) {
		$this->html = $html;
		$this->placeholders = $placeholders;
	}

	/**
	 * @return string HTML
	 */
	public function getHtml() {
		return $this->html;
	}

	/**
	 * Get information about the placeholders contained in the HTML
	 *
	 * Naming this is hard as it confusingly contains both
	 * meta data for single placeholders (e.g. wikibase-terms-list-item)
	 * as well as
	 * some placeholder record keeping (e.g. wikibase-view-chunks)
	 * which would better be depicted as a hierarchie than a group of peers
	 *
	 * @return array
	 */
	public function getPlaceholders() {
		return $this->placeholders;
	}

}
