<?php

namespace Wikibase\Client\Hooks;

use Site;
use SiteStore;
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
 * @author Marius Hoch < hoo@online.de >
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
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var string[]
	 */
	private $siteIdsToOutput;

	/**
	 * @param string $localSiteId
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteStore $siteStore
	 * @param string[] $siteIdsToOutput
	 */
	public function __construct( $localSiteId, SiteLinkLookup $siteLinkLookup, SiteStore $siteStore,
		array $siteIdsToOutput
	) {
		$this->localSiteId = $localSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteStore = $siteStore;
		$this->siteIdsToOutput = $siteIdsToOutput;
	}

	/**
	 * @param Title $title
	 *
	 * @return array[] array of arrays of link attributes, sorted by site group id
	 */
	public function buildProjectLinkSidebar( Title $title ) {
		return $this->buildSidebarFromSiteLinks( $this->getSiteLinks( $title ) );
	}

	/**
	 * @param SiteLink[] $siteLinks
	 *
	 * @return array[] array of arrays of link attributes, sorted by site group id
	 */
	private function buildSidebarFromSiteLinks( array $siteLinks ) {
		$result = array();

		foreach ( $siteLinks as $siteLink ) {
			if ( !in_array( $siteLink->getSiteId(), $this->siteIdsToOutput ) ) {
				continue;
			}
			$site = $this->siteStore->getSite( $siteLink->getSiteId() );
			if ( $site === null ) {
				continue;
			}

			if ( !isset( $result[$site->getGroup()] ) ) {
				$result[$site->getGroup()] = array();
			}

			// Index by site group and global id
			$result[$site->getGroup()][$site->getGlobalId()] = $this->buildSidebarLink( $siteLink, $site );
		}

		return $this->sortAndFlattenSidebar( $result );
	}

	/**
	 * @param array[]
	 *
	 * @return array[] array of arrays of link attributes, sorted by site group id
	 */
	private function sortAndFlattenSidebar( array $links ) {
		$result = array();

		ksort( $links ); // Sort by group id

		foreach( $links as $linksPerGroup ) {
			ksort( $linksPerGroup ); // Sort individual arrays by global site id
			$result = array_merge( $result, array_values( $linksPerGroup ) );
		}

		return $result;
	}

	/**
	 * @param Title $title
	 *
	 * @return SiteLink[]
	 */
	private function getSiteLinks( Title $title ) {
		$siteLink = new SiteLink( $this->localSiteId, $title->getFullText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId === null ) {
			return array();
		}

		return $this->siteLinkLookup->getSiteLinksForItem( $itemId );
	}

	/**
	 * @param SiteLink $siteLink
	 * @param Site $site
	 *
	 * @return string[]
	 */
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
