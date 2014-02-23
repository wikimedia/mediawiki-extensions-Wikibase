<?php
namespace Wikibase\Client;

use Wikibase\SiteLinkLookup;
use Wikibase\EntityLookup;

/**
 * Provides access to sitelinks on repo.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinkProvider {

	/**
	 * @var string
	 */
	protected $localSiteId;

	/**
	 * @var SiteLinkLookup
	 */
	protected $siteLinkLookup;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @param string $localSiteId global id of the client wiki
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityLookup $entityLookup
	 */
	public function __construct( $localSiteId, SiteLinkLookup $siteLinkLookup, EntityLookup $entityLookup ) {
		$this->localSiteId = $localSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * Finds the corresponding item on the repository and returns the item's site links.
	 * The sitelinks optionally also include badges, although this is more expensive.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param bool? $includeBadges
	 *
	 * @return SiteLink[]
	 */
	protected function getSiteLinks( Title $title, $includeBadges = false ) {
		$siteLink = new SiteLink( $this->localSiteId, $title->getText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId === null ) {
			return array();
		}

		// we need this check until there is a proper SiteLinkLookup which also provides badges
		if ( $includeBadges === false ) {
			return $this->siteLinkLookup->getSiteLinksForItem( $itemId );
		}

		$item = $this->entityLookup->getEntity( $itemId );
		if ( $item === null ) {
			return array();
		}
		return $item->getSiteLinks();
	}

}
