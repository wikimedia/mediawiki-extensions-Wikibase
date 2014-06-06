<?php

namespace Wikibase\Client;

use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

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
	 * @var array
	 */
	private $cachedItems = array();

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
	 * Finds the corresponding item on the repository
	 * and caches the result in an array.
	 *
	 * @param Title $title
	 *
	 * @return Item|null
	 */
	private function getItem( Title $title ) {
		$prefixedText = $title->getPrefixedText();

		if ( !array_key_exists( $prefixedText, $this->cachedItems ) ) {
			$itemId = $this->siteLinkLookup->getItemIdForLink(
				$this->localSiteId,
				$prefixedText
			);

			if ( $itemId === null ) {
				$this->cachedItems[$prefixedText] = null;
			}
			else {
				$this->cachedItems[$prefixedText] = $this->entityLookup->getEntity( $itemId );
			}
		}

		return $this->cachedItems[$prefixedText];
	}

}
