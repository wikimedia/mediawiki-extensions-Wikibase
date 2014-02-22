<?php

namespace Wikibase\Client;

use Sites;
use Title;
use Wikibase\DataModel\SiteLink;
use Wikibase\SiteLinkLookup;

/**
 * Outputs a sidebar section for other project links.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class OtherProjectsSidebarGenerator {

	/**
	 * @var string
	 */
	private $localSiteId;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var Sites
	 */
	private $sites;

	/**
	 * @var String[]
	 */
	private $siteIdsToOutput;

	/**
	 * @param string $localSiteId
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param Sites $sites
	 * @param String[] $siteIdsToOutput
	 */
	public function __construct( $localSiteId, SiteLinkLookup $siteLinkLookup, Sites $sites, array $siteIdsToOutput ) {
		$this->localSiteId = $localSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->sites = $sites;
		$this->siteIdsToOutput = $siteIdsToOutput;
	}

	/**
	 * @param Title $title
	 *
	 * @return array
	 */
	public function buildProjectLinkSidebar( Title $title ) {
		$siteLinks = $this->getSiteLinks( $title );

		$result = array();

		foreach ( $siteLinks as $siteLink ) {
			if ( !in_array( $siteLink->getSiteId(), $this->siteIdsToOutput ) ) {
				continue;
			}

			$result[] = $this->buildSidebarLink( $siteLink );
		}

		return $result;
	}

	private function getSiteLinks( Title $title ) {
		$siteLink = new SiteLink( $this->localSiteId, $title->getFullText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId === null ) {
			return array();
		}

		return $this->siteLinkLookup->getSiteLinksForItem( $itemId );
	}

	private function buildSidebarLink( SiteLink $siteLink ) {
		$site = $this->sites->getSite( $siteLink->getSiteId() );

		$node = array(
			'msg' => 'wb-otherprojects-' . $siteLink->getSiteId(),
			'class' => 'wb-otherproject-link wb-otherproject-' . $siteLink->getSiteId(),
			'href' => $site->getPageUrl( $siteLink->getPageName() )
		);

		$siteLanguageCode = $site->getLanguageCode();
		if ( $siteLanguageCode !== null ) {
			$node['hreflang'] = $siteLanguageCode;
		}

		return $node;
	}
}
