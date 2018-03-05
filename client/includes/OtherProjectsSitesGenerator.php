<?php

namespace Wikibase\Client;

use Site;
use SiteLookup;

/**
 * Generates a list of sites that should be displayed in the "Other projects" sidebar.
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSitesGenerator implements OtherProjectsSitesProvider {

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var string
	 */
	private $localSiteId;

	/**
	 * @var string[]
	 */
	private $specialSiteGroups;

	/**
	 * @param SiteLookup $siteLookup
	 * @param string $localSiteId
	 * @param string[] $specialSiteGroups
	 */
	public function __construct( SiteLookup $siteLookup, $localSiteId, array $specialSiteGroups ) {
		$this->siteLookup = $siteLookup;
		$this->localSiteId = $localSiteId;
		$this->specialSiteGroups = $specialSiteGroups;
	}

	/**
	 * Get the site ids of other projects to use.
	 *
	 * @param array $siteLinkGroups
	 * @return string[]
	 */
	public function getOtherProjectsSiteIds( array $siteLinkGroups ) {
		$localSite = $this->getLocalSite();

		if ( $localSite === null ) {
			wfWarn( 'Site not found for ' . $this->localSiteId );
			return [];
		}

		$currentGroupId = $localSite->getGroup();
		$otherProjectsSiteIds = [];

		$this->expandSpecialGroups( $siteLinkGroups );
		foreach ( $siteLinkGroups as $groupId ) {
			if ( $groupId === $currentGroupId ) {
				continue;
			}

			$siteToAdd = $this->getSiteForGroup( $groupId, $localSite->getLanguageCode() );
			if ( $siteToAdd ) {
				$otherProjectsSiteIds[] = $siteToAdd->getGlobalId();
			}
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
	 * @param string $currentLanguageCode
	 *
	 * @return Site|null
	 */
	private function getSiteForGroup( $groupId, $currentLanguageCode ) {
		$siteGroupList = $this->siteLookup->getSites()->getGroup( $groupId );
		if ( $siteGroupList->count() === 1 ) {
			return $siteGroupList[0];
		}

		/** @var Site $site */
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

		$groups = array_diff( $groups, [ 'special' ] );
		$groups = array_merge( $groups, $this->specialSiteGroups );
	}

	/**
	 * @return Site
	 */
	private function getLocalSite() {
		return $this->siteLookup->getSite( $this->localSiteId );
	}

}
