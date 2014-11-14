<?php

namespace Wikibase\Client;

use Site;
use SiteList;

/**
 * Provides a list of sites that should be displayed in the "other project" sidebar
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSitesProvider {

	/**
	 * @param SiteList $sites
	 */
	private $sites;

	/**
	 * @var Site
	 */
	private $currentSite;

	/**
	 * @var string[]
	 */
	private $specialSiteGroups;

	/**
	 * @param SiteList $sites
	 * @param Site $currentSite
	 * @param string[] $specialSiteGroups
	 */
	public function __construct( SiteList $sites, Site $currentSite, array $specialSiteGroups ) {
		$this->sites = $sites;
		$this->currentSite = $currentSite;
		$this->specialSiteGroups = $specialSiteGroups;
	}

	/**
	 * Provides a list of sites to link to in the "other project" sidebar
	 *
	 * This list contains the wiki in the same language if it exists for each other site groups and the wikis alone in their
	 * sites groups (like commons)
	 *
	 * @param string[] $supportedSiteGroupIds
	 *
	 * @return SiteList
	 */
	public function getOtherProjectsSites( array $supportedSiteGroupIds ) {
		$currentGroupId = $this->currentSite->getGroup();
		$otherProjectsSites = new SiteList();

		$this->expandSpecialGroups( $supportedSiteGroupIds );
		foreach ( $supportedSiteGroupIds as $groupId ) {
			if ( $groupId === $currentGroupId ) {
				continue;
			}

			$siteToAdd = $this->getSiteForGroup( $groupId );
			if ( $siteToAdd ) {
				$otherProjectsSites[] = $siteToAdd;
			}
		}

		return $otherProjectsSites;
	}

	/**
	 * Get the site ids of other projects to use.
	 *
	 * @param array $supportedSiteGroupIds
	 * @return string[]
	 */
	public function getOtherProjectsSiteIds( array $supportedSiteGroupIds ) {
		$otherProjectsSites = $this->getOtherProjectsSites( $supportedSiteGroupIds );

		$otherProjectsSiteIds = array();
		foreach ( $otherProjectsSites as $site ) {
			$otherProjectsSiteIds[] = $site->getGlobalId();
		}

		return $otherProjectsSiteIds;
	}

	/**
	 * Returns the site to link to for a given group or null
	 *
	 * If there is only one site in this group (like for commons) this site is returned else the site in the same language
	 * as the current site is returned
	 *
	 * @param string $groupId
	 *
	 * @return Site|null
	 */
	private function getSiteForGroup( $groupId ) {
		$siteGroupList = $this->sites->getGroup( $groupId );
		if ( $siteGroupList->count() === 1 ) {
			return $siteGroupList[0];
		}

		$currentLanguageCode = $this->currentSite->getLanguageCode();
		foreach ( $siteGroupList as $site ) {
			if ( $site->getLanguageCode() === $currentLanguageCode ) {
				return $site;
			}
		}

		return null;
	}

	/**
	 * @param array &$groups
	 */
	private function expandSpecialGroups( &$groups ) {
		if ( !in_array( 'special', $groups ) ) {
			return;
		}

		$groups = array_diff( $groups, array( 'special' ) );
		$groups = array_merge( $groups, $this->specialSiteGroups );
	}
}
