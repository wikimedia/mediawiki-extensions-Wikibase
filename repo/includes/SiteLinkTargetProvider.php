<?php

namespace Wikibase\Repo;

use MediaWiki\Site\Site;
use MediaWiki\Site\SiteList;
use MediaWiki\Site\SiteLookup;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkTargetProvider {

	/** @var string[] */
	private array $siteLinkGroups;

	/**
	 * @param SiteLookup $siteLookup
	 * @param string[] $siteLinkGroups
	 * @param string[] $specialSiteGroups
	 */
	public function __construct(
		private readonly SiteLookup $siteLookup,
		array $siteLinkGroups,
		array $specialSiteGroups = [],
	) {
		// As the special sitelink group actually just wraps multiple groups
		// into one we have to replace it with the actual groups
		$this->siteLinkGroups = $this->substituteSpecialSiteGroups( $siteLinkGroups, $specialSiteGroups );
	}

	/**
	 * Returns the list of sites that are suitable as a sitelink target.
	 */
	public function getSiteList(): SiteList {
		$sites = new SiteList();
		$allSites = $this->siteLookup->getSites();

		/** @var Site $site */
		foreach ( $allSites as $site ) {
			if ( in_array( $site->getGroup(), $this->siteLinkGroups ) ) {
				$sites->append( $site );
			}
		}

		return $sites;
	}

	/**
	 * @param string[] $groups
	 * @return string[]
	 */
	private function substituteSpecialSiteGroups( array $groups, array $specialSiteGroups ): array {
		if ( !in_array( 'special', $groups ) ) {
			return $groups;
		}

		return array_diff(
			array_merge( $groups, $specialSiteGroups ),
			[ 'special' ]
		);
	}

}
