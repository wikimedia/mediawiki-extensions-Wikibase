<?php

namespace Wikibase\Client\Scribunto;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindings {

	/* @var string */
	protected $siteId;

	/**
	 * @param string $siteId
	 */
	public function __construct( $siteId ) {
		$this->siteId = $siteId;
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * I can see this becoming part of mw.site in the Scribunto extension.
	 *
	 * @since 0.5
	 *
	 * @return array
	 */
	public function getGlobalSiteId() {
		return array( $this->siteId );
	}
}
