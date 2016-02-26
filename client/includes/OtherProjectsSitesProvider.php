<?php

namespace Wikibase\Client;

/**
 * Provides a list of sites that should be displayed in the "Other projects" sidebar
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
interface OtherProjectsSitesProvider {

	/**
	 * Get the site ids of other projects to use.
	 *
	 * @param array $siteLinkGroups
	 * @return string[]
	 */
	public function getOtherProjectsSiteIds( array $siteLinkGroups );

}
