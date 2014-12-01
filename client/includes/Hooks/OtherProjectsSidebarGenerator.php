<?php

namespace Wikibase\Client\Hooks;

use Site;
use SiteList;
use Title;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

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
	 * @var SiteList
	 */
	private $siteList;

	/**
	 * @var string[]
	 */
	private $siteIdsToOutput;

	/**
	 * @param string $localSiteId
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteList $siteList
	 * @param string[] $siteIdsToOutput
	 */
	public function __construct( $localSiteId, SiteLinkLookup $siteLinkLookup, SiteList $siteList,
		array $siteIdsToOutput
	) {
		$this->localSiteId = $localSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteList = $siteList;
		$this->siteIdsToOutput = $siteIdsToOutput;
	}

	/**
	 * @param Title $title
	 *
	 * @return array
	 */
	public function buildProjectLinkSidebar( Title $title ) {
		return $this->buildSidebarFromSiteLinks( $this->getSiteLinks( $title ) );
	}

	/**
	 * @param SiteLink[] $siteLinks
	 *
	 * @return array
	 */
	private function buildSidebarFromSiteLinks( array $siteLinks ) {
		$result = array();

		foreach ( $siteLinks as $siteLink ) {
			if ( !in_array( $siteLink->getSiteId(), $this->siteIdsToOutput ) ) {
				continue;
			}
			$site = $this->siteList->getSite( $siteLink->getSiteId() );
			if ( $site === null ) {
				continue;
			}

			$result[] = $this->buildSidebarLink( $siteLink, $site );
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

	private function buildSidebarLink( SiteLink $siteLink, Site $site ) {
		$node = array(
			'msg' => 'wikibase-otherprojects-' . $site->getGroup(),
			'class' => 'wb-otherproject-link wb-otherproject-' . $site->getGroup(),
			'href' => $site->getPageUrl( $siteLink->getPageName() )
		);

		$siteLanguageCode = $site->getLanguageCode();
		if ( $siteLanguageCode !== null ) {
			$node['hreflang'] = $siteLanguageCode;
		}

		return $node;
	}
}
