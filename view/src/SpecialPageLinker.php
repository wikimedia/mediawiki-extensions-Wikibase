<?php

namespace Wikibase\View;

/**
 * A service returning a URL for a specific special page with optional parameters.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface SpecialPageLinker {

	/**
	 * Returns the URL to a special page with optional params
	 *
	 * @param string $pageName
	 * @param string[] $subPageParams Parameters to be added as slash-separated sub pages
	 *
	 * @return string
	 */
	public function getLink( $pageName, array $subPageParams = [] );

}
