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
	 * Finds the corresponding item on the repository and
	 * returns all the item's site links.
	 *
	 * @since 0.5
	 *
	 * @param string $title
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinks( $title ) {
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
	 * @param string $title
	 * @param string $siteId
	 *
	 * @return SiteLink|null
	 */
	public function getSiteLink( $title, $siteId ) {
		$item = $this->getItem( $title );
		if ( $item === null || !$item->hasLinkToSite( $siteId ) ) {
			return null;
		}
		return $item->getSiteLink( $siteId );
	}

	/**
	 * Finds the corresponding item on the repository.
	 *
	 * @param string $title
	 *
	 * @return Item|null
	 */
	private function getItem( $title ) {
		$id = $this->siteLinkLookup->getItemIdForLink( $this->localSiteId, $title );

		if ( $id === null ) {
			return null;
		}

		return $this->entityLookup->getEntity( $id );
	}

}
