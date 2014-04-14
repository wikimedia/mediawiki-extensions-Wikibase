<?php

namespace Wikibase\Client;

use Wikibase\SiteLinkLookup;
use Wikibase\EntityLookup;
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
	private $localSiteId;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

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
	 * Finds the corresponding item on the repository and
	 * returns all the item's site links.
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
	 * Finds the corresponding item on the repository and
	 * returns the item's site link for the given site id.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param string $siteId
	 *
	 * @return SiteLink|null
	 */
	public function getSiteLink( Title $title, $siteId ) {
		$item = $this->getItem( $title );
		if ( $item === null || !$item->hasLinkToSite( $siteId ) ) {
			return null;
		}
		return $item->getSiteLink( $siteId );
	}

	/**
	 * Finds the corresponding item on the repository.
	 *
	 * @param Title $title
	 *
	 * @return Item|null
	 */
	private function getItem( Title $title ) {
		$itemId = $this->siteLinkLookup->getItemIdForLink( $this->localSiteId, $title->getPrefixedText() );

		if ( $itemId === null ) {
			return null;
		}

		return $this->entityLookup->getEntity( $itemId );
	}

}
