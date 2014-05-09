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
	 * @var string[]
	 */
	private $supportedSiteGroupIds;

	public function __construct( SiteStore $siteStore, array $supportedSiteGroupIds ) {
		$this->siteStore = $siteStore;
		$this->supportedSiteGroupIds = $supportedSiteGroupIds;
	}

	/**
	 * @param Site $currentSite
	 * @return SiteList
	 */
	public function getOtherProjectsSites( Site $currentSite ) {
		$currentGroupId = $currentSite->getGroup();
		$currentLanguageCode = $currentSite->getLanguageCode();
		$siteList = $this->siteStore->getSites();
		$otherProjectsSites = new SiteList();

		foreach ( $this->supportedSiteGroupIds as $groupId ) {
			if ( $groupId === $currentGroupId ) {
				continue;
			}

			$siteGroupList = $siteList->getGroup( $groupId );
			if ( $siteGroupList->count() == 1 ) {
				$otherProjectsSites[] = $siteGroupList[0];
			} else {
				foreach ( $siteGroupList as $site ) {
					if ( $site->getLanguageCode() === $currentLanguageCode ) {
						$otherProjectsSites[] = $site;
					}
				}
			}
		}

		return $otherProjectsSites;
	}
} 