<?php

namespace Wikibase\Client;

use Site;
use SiteList;
use SiteStore;

/**
 * Provides a list of sites that should be displayed in the "other project" sidebar
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class OtherProjectsSitesProvider {

	/**
	 * @param SiteStore $siteStore
	 */
	private $siteStore;

	/**
	 * @var Site
	 */
	private $currentSite;

	/**
	 * @param SiteStore $siteStore
	 * @param Site $currentSite
	 */
	public function __construct( SiteStore $siteStore, Site $currentSite ) {
		$this->siteStore = $siteStore;
		$this->currentSite = $currentSite;
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

		foreach ( $supportedSiteGroupIds as $groupId ) {
			if ( $groupId === $currentGroupId ) {
				continue;
			}

			$siteToAdd = $this->getSiteForGroup( $groupId );
			if ( $siteToAdd != null ) {
				$otherProjectsSites[] = $siteToAdd;
			}
		}

		return $otherProjectsSites;
	}

	/**
	 * Returns the site to link to for a given group or null
	 *
	 * If there is only one site in this group (like for commons) this site is returned else the site in the same language
	 * as the current site is returned
	 *
	 * @param string $groupId
	 *
	 * @return Site
	 */
	private function getSiteForGroup( $groupId ) {
		$siteGroupList = $this->siteStore->getSites()->getGroup( $groupId );
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
}