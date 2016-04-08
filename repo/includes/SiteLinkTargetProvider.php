<?php

namespace Wikibase\Repo;

use Site;
use SiteList;
use SiteStore;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel K
 * @author Addshore
 * @author Marius Hoch < hoo@online.de >
 * @author Thiemo Mättig
 */
class SiteLinkTargetProvider {

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var string[]
	 */
	private $specialSiteGroups;

	/**
	 * @param SiteStore $siteStore
	 * @param string[] $specialSiteGroups
	 */
	public function __construct(
		SiteStore $siteStore,
		array $specialSiteGroups = array()
	) {
		$this->siteStore = $siteStore;
		$this->specialSiteGroups = $specialSiteGroups;
	}

	/**
	 * Returns the list of sites that is suitable as a sitelink target.
	 *
	 * @return SiteList alphabetically ordered by the site's global identifiers.
	 */
	public function getSiteList() {
		$sites = $this->siteStore->getSites();

		// Because of the way SiteList is implemented this will not order the array returned by
		// SiteList::getGlobalIdentifiers.
		$sites->uasort( function( Site $a, Site $b ) {
			return strnatcasecmp( $a->getGlobalId(), $b->getGlobalId() );
		} );

		return $sites;
	}

	/**
	 * Returns the list of sites that is suitable as a sitelink target.
	 *
	 * @param string[] $groups sitelink groups to get
	 *
	 * @return SiteList alphabetically ordered by the site's global identifiers.
	 */
	public function getSiteListForGroups( array $groups ) {
		// As the special sitelink group actually just wraps multiple groups
		// into one we have to replace it with the actual groups
		$this->substituteSpecialSiteGroups( $groups );

		$sites = new SiteList();
		/** @var Site $site */
		foreach ( $this->getSiteList() as $site ) {
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

		$groups = array_diff( $groups, array( 'special' ) );
		$groups = array_merge( $groups, $this->specialSiteGroups );
	}

}
