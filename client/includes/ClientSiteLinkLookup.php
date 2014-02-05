<?php

namespace Wikibase\Client;

use Wikibase\SiteLinkLookup;
use Wikibase\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Title;

/**
 * Provides access to sitelinks on repo.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ClientSiteLinkLookup {

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
	 * Finds the corresponding item on the repository and returns
	 * all the item's site links including badges.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinks( Title $title ) {
		$item = $this->getItem( $title );
		if ( $item === null ) {
			return array();
		}
		return $item->getSiteLinks();
	}

	/**
	 * Finds the corresponding item on the repository and returns
	 * the item's site link for the given site including badges.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param string $site
	 *
	 * @return SiteLink
	 */
	public function getSiteLink( Title $title, $site ) {
		$item = $this->getItem( $title );
		if ( $item === null || !$item->hasLinkToSite( $site ) ) {
			return null;
		}
		return $item->getSiteLink( $site );
	}

	/**
	 * Finds the corresponding item on the repository.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 *
	 * @return Item
	 */
	public function getItem( Title $title ) {
		$siteLink = new SiteLink( $this->localSiteId, $title->getText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId === null ) {
			return null;
		}

		return $this->entityLookup->getEntity( $itemId );
	}

}
