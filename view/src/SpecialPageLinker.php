<?php

namespace Wikibase\View;

/**
 * A service returning a URL for a specific special page with optional parameters
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
interface SpecialPageLinker {
	/**
	 * Returns the URL to a special page with optional params
	 *
	 * @since 0.5
	 * @param string $pageName
	 * @param string[] $params
	 */
	public function getLink( $pageName, $params = array() );
}
