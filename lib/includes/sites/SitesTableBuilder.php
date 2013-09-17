<?php

namespace Wikibase;

use SiteSQLStore;

/**
 * Builds the sites table
 *
 * @since 0.5
 *
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SitesTableBuilder {

	/**
	 * Inserts sites into the sites table.
	 * Existing entries in the sites table are not modified.
	 *
	 * @param Site[] $sites
	 */
	public function addSiteMatrix( array $sites ) {
		$store = SiteSQLStore::newInstance();
		$store->getSites( "nocache" );
		$store->saveSites( $sites );
	}

}
