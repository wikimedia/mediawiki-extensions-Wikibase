<?php

namespace Wikibase\Repo;

use Site;
use SiteList;
use SiteLookup;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkTargetProvider {

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var string[]
	 */
	private $specialSiteGroups;

	/**
	 * @param SiteLookup $siteLookup
	 * @param string[] $specialSiteGroups
	 */
	public function __construct( SiteLookup $siteLookup, array $specialSiteGroups = [] ) {
		$this->siteLookup = $siteLookup;
		$this->specialSiteGroups = $specialSiteGroups;
	}

	/**
	 * Returns the list of sites that is suitable as a sitelink target.
	 *
	 * @param string[] $groups sitelink groups to get
	 *
	 * @return SiteList
	 */
	public function getSiteList( array $groups ) {
		// As the special sitelink group actually just wraps multiple groups
		// into one we have to replace it with the actual groups
		$this->substituteSpecialSiteGroups( $groups );

		$sites = new SiteList();
		$allSites = $this->siteLookup->getSites();

		/** @var Site $site */
		foreach ( $allSites as $site ) {
			if ( in_array( $site->getGroup(), $groups ) ) {
				$sites->append( $site );
			}
		}

		return $sites;
	}

	/**
	 * @param string[] &$groups
	 */
	private function substituteSpecialSiteGroups( &$groups ) {
		if ( !in_array( 'special', $groups ) ) {
			return;
		}

		$groups = array_diff( $groups, [ 'special' ] );
		$groups = array_merge( $groups, $this->specialSiteGroups );
	}

}
