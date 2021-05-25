<?php

namespace Wikibase\Repo;

use BagOStuff;
use HashBagOStuff;
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
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @param SiteLookup $siteLookup
	 * @param string[] $specialSiteGroups
	 * @param BagOStuff|null $cache
	 */
	public function __construct( SiteLookup $siteLookup, array $specialSiteGroups = [], BagOStuff $cache = null ) {
		$this->siteLookup = $siteLookup;
		$this->specialSiteGroups = $specialSiteGroups;
		$this->cache = $cache ?? new HashBagOStuff();
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

	public function getSiteListGlobalIdentifiers( array $groups ) {
		$key = $this->cache->makeKey(
			'wikibase-site-link-target-provider',
			'global-identifiers',
			implode( ',', $groups )
		);
		return $this->cache->getWithSetCallback(
			$key,
			3600,
			function () use ( $groups ) {
				return $this->getSiteList( $groups )->getGlobalIdentifiers();
			}
		);
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
