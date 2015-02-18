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
	public function __construct(
		$localSiteId,
		SiteLinkLookup $siteLinkLookup,
		SiteStore $siteStore,
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
	 * @return array[] Array of arrays of of attributes describing sidebar links, sorted by the
	 * site's group and global ids.
	 */
	public function buildProjectLinkSidebar( Title $title ) {
		return $this->buildSidebarFromSiteLinks( $this->getSiteLinks( $title ) );
	}

	/**
	 * @param SiteLink[] $siteLinks
	 *
	 * @return array[] Array of arrays of of attributes describing sidebar links, sorted by the
	 * site's group and global ids.
	 */
	private function buildSidebarFromSiteLinks( array $siteLinks ) {
		$linksByGroup = array();

		foreach ( $siteLinks as $siteLink ) {
			if ( !in_array( $siteLink->getSiteId(), $this->siteIdsToOutput ) ) {
				continue;
			}

			$site = $this->siteStore->getSite( $siteLink->getSiteId() );

			if ( $site !== null ) {
				$group = $site->getGroup();
				$globalId = $site->getGlobalId();
				// Index by site group and global id
				$linksByGroup[$group][$globalId] = $this->buildSidebarLink( $siteLink, $site );
			}
		}

		return $this->sortAndFlattenSidebar( $linksByGroup );
	}

	/**
	 * The arrays of link attributes are indexed by site group and by global site id.
	 * Sort them by both and then return the flattened array.
	 *
	 * @param array[] $linksByGroup
	 *
	 * @return array[] Array of arrays of of attributes describing sidebar links, sorted by the
	 * site's group and global ids.
	 */
	private function sortAndFlattenSidebar( array $linksByGroup ) {
		$result = array();

		ksort( $linksByGroup ); // Sort by group id

		foreach ( $linksByGroup as $linksPerGroup ) {
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
	 * @return string[] Array of attributes describing a sidebar link.
	 */
	private function buildSidebarLink( SiteLink $siteLink, Site $site ) {
		$attributes = array(
			'msg' => 'wikibase-otherprojects-' . $site->getGroup(),
			'class' => 'wb-otherproject-link wb-otherproject-' . $site->getGroup(),
			'href' => $site->getPageUrl( $siteLink->getPageName() )
		);

		$siteLanguageCode = $site->getLanguageCode();
		if ( $siteLanguageCode !== null ) {
			$attributes['hreflang'] = $siteLanguageCode;
		}

		return $attributes;
	}

}
