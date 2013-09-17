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
	public function saveSites( array $sites ) {
		$store = SiteSQLStore::newInstance();
		$store->getSites( "nocache" );
		$store->saveSites( $sites );
	}

	/**
	 * @param Site[] $sites
	 * @param string $siteGroup
	 *
	 * @return Site[]
	 */
	public function addInterwikiIds( array $sites, $siteGroup ) {
		foreach( $sites as $site ) {
			if( $site->getGroup() === $siteGroup ) {
				$localId = $site->getLanguageCode();

				$site->addNavigationId( $localId );
				$site->addInterwikiId( $localId );
			}
		}

		return $sites;
	}

}
