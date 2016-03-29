<?php

namespace Wikibase\Client\Hooks;

use Hooks;
use Site;
use SiteStore;
use Title;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Outputs a sidebar section for other project links.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @return array[] Array of arrays of attributes describing sidebar links, sorted by the site's
	 * group and global ids.
	 */
	public function buildProjectLinkSidebar( Title $title ) {
		$itemId = $this->getItemId( $title );
		if ( !$itemId ) {
			return array();
		}

		$sidebar = $this->buildPreliminarySidebarFromSiteLinks( $this->getSiteLinks( $itemId ) );
		$sidebar = $this->runHook( $itemId, $sidebar );

		return $this->sortAndFlattenSidebar( $sidebar );
	}

	/**
	 * @param ItemId $itemId
	 * @param array $sidebar
	 *
	 * @return array
	 */
	private function runHook( ItemId $itemId, array $sidebar ) {
		$newSidebar = $sidebar;

		Hooks::run( 'WikibaseClientOtherProjectsSidebar', array( $itemId, &$newSidebar ) );

		if ( $newSidebar === $sidebar ) {
			return $sidebar;
		}

		if ( !is_array( $newSidebar ) || !$this->isValidSidebar( $newSidebar ) ) {
			wfLogWarning( 'Other projects sidebar data invalid after hook run.' );
			return $sidebar;
		}

		return $newSidebar;
	}

	/**
	 * @param array $sidebar
	 * @return bool
	 */
	private function isValidSidebar( array $sidebar ) {
		// Make sure all required array keys are set and are string.
		foreach ( $sidebar as $siteGroup => $perSiteGroup ) {
			if ( !is_string( $siteGroup ) || !is_array( $perSiteGroup ) ) {
				return false;
			}

			foreach ( $perSiteGroup as $siteId => $perSite ) {
				if ( !is_string( $siteId )
					|| !isset( $perSite['msg'] )
					|| !isset( $perSite['class'] )
					|| !isset( $perSite['href'] )
					|| !is_string( $perSite['msg'] )
					|| !is_string( $perSite['class'] )
					|| !is_string( $perSite['href'] )
				) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param SiteLink[] $siteLinks
	 *
	 * @return array[] Arrays of link attributes indexed by site group and by global site id.
	 */
	private function buildPreliminarySidebarFromSiteLinks( array $siteLinks ) {
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

		return $linksByGroup;
	}

	/**
	 * The arrays of link attributes are indexed by site group and by global site id.
	 * Sort them by both and then return the flattened array.
	 *
	 * @param array[] $linksByGroup
	 *
	 * @return array[] Array of arrays of attributes describing sidebar links, sorted by the site's
	 * group and global ids.
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
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 */
	private function getSiteLinks( ItemId $itemId ) {
		return $this->siteLinkLookup->getSiteLinksForItem( $itemId );
	}

	/**
	 * @param Title $title
	 *
	 * @return ItemId|null
	 */
	private function getItemId( Title $title ) {
		$siteLink = new SiteLink( $this->localSiteId, $title->getPrefixedText() );
		return $this->siteLinkLookup->getItemIdForSiteLink( $siteLink );
	}

	/**
	 * @param SiteLink $siteLink
	 * @param Site $site
	 *
	 * @return string[] Array of attributes describing a sidebar link.
	 */
	private function buildSidebarLink( SiteLink $siteLink, Site $site ) {
		// Messages in the WikimediaMessages extension (as of 2015-03-31):
		// wikibase-otherprojects-commons
		// wikibase-otherprojects-testwikidata
		// wikibase-otherprojects-wikidata
		// wikibase-otherprojects-wikinews
		// wikibase-otherprojects-wikipedia
		// wikibase-otherprojects-wikiquote
		// wikibase-otherprojects-wikisource
		// wikibase-otherprojects-wikivoyage
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
