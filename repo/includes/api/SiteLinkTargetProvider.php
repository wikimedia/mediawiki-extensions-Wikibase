<?php

namespace Wikibase\Api;

use Site;
use SiteList;
use SiteStore;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 *
 * @author Daniel K
 * @author Adam Shorland
 */
class SiteLinkTargetProvider {

	/**
	 * @var SiteStore
	 */
	protected $siteStore;

	/**
	 * @param SiteStore $siteStore
	 */
	public function __construct( SiteStore $siteStore ) {
		$this->siteStore = $siteStore;
	}

	/**
	 * Returns the list of sites that is suitable as a sitelink target.
	 *
	 * @param array $groups sitelink groups to get
	 *
	 * @return SiteList
	 */
	public function getSiteList( array $groups ) {
		$sites = new SiteList();
		$allSites = $this->siteStore->getSites();

		/* @var Site $site */
		foreach ( $allSites as $site ) {
			if ( in_array( $site->getGroup(), $groups ) ) {
				$sites->append( $site );
			}
		}

		return $sites;
	}

}