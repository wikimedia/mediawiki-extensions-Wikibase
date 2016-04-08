<?php

namespace Wikibase\Repo;

use BagOStuff;
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
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var string[]
	 */
	private $specialSiteGroups;

	/**
	 * @param SiteStore $siteStore
	 * @param BagOStuff $cache
	 * @param string[] $specialSiteGroups
	 */
	public function __construct(
		SiteStore $siteStore,
		BagOStuff $cache,
		array $specialSiteGroups = array()
	) {
		$this->siteStore = $siteStore;
		$this->cache = $cache;
		$this->specialSiteGroups = $specialSiteGroups;
	}

	/**
	 * Returns the list of sites that is suitable as a sitelink target.
	 *
	 * @param string[] $groups sitelink groups to get
	 *
	 * @return SiteList alphabetically ordered by the site's global identifiers.
	 */
	public function getSiteList( array $groups ) {
		// As the special sitelink group actually just wraps multiple groups
		// into one we have to replace it with the actual groups
		$this->substituteSpecialSiteGroups( $groups );

		$cacheKey = $this->cache->makeKey( __METHOD__, 'allSites' );
		$allSites = $this->cache->get( $cacheKey );
		if ( !$allSites ) {
			$allSites = $this->siteStore->getSites();

			// Because of the way SiteList is implemented this will not order the array returned by
			// SiteList::getGlobalIdentifiers.
			$allSites->uasort( function( Site $a, Site $b ) {
				return strnatcasecmp( $a->getGlobalId(), $b->getGlobalId() );
			} );

			$this->cache->set( $cacheKey, $allSites, 300 );
		}

		$sites = new SiteList();
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

		$groups = array_diff( $groups, array( 'special' ) );
		$groups = array_merge( $groups, $this->specialSiteGroups );
	}

}
