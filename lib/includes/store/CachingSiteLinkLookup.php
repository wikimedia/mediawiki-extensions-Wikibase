<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * SiteLinkLookup implementation that caches the obtained data where it makes sense.
 * Note: This doesn't implement any means of purging or data invalidation beyond the cache
 * timeout.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class CachingSiteLinkLookup implements SiteLinkLookup {

	/**
	 * @var SiteLinkLookup
	 */
	private $lookup;

	/**
	 * The cache to use for caching entities.
	 *
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $cacheTimeout;

	/**
	 * @param SiteLinkLookup $siteLinkLookup The lookup to use
	 * @param BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds. Defaults to 3600 (1 hour).
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		BagOStuff $cache,
		$cacheDuration = 3600
	) {
		$this->lookup = $siteLinkLookup;
		$this->cache = $cache;
		$this->cacheTimeout = $cacheDuration;
	}

	/**
	 * @see SiteLinkLookup::getItemIdForLink
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return ItemId|null
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle ) {
		$itemId = $this->cache->get( $this->getItemIdForLinkCacheKey( $globalSiteId, $pageTitle ) );

		if ( $itemId === false ) {
			$itemId = $this->getAndCacheItemIdForLink( $globalSiteId, $pageTitle );
		}

		if ( !is_string( $itemId ) ) {
			return null;
		}

		return new ItemId( $itemId );
	}

	/**
	 * @see SiteLinkLookup::getLinks
	 * This is uncached!
	 *
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return array[]
	 */
	public function getLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() ) {
		// Caching this would be rather complicated for little to no benefit.
		return $this->lookup->getLinks( $numericIds, $siteIds, $pageNames );
	}

	/**
	 * Returns an array of SiteLink objects for an item. If the item isn't known or not an Item,
	 * an empty array is returned.
	 *
	 * @since 0.4
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinksForItem( ItemId $itemId ) {
		$cacheKey = 'wikibase-sitelinks:' . $itemId->getSerialization();
		$siteLinks = $this->cache->get( $cacheKey );

		if ( !is_array( $siteLinks ) ) {
			$siteLinks = $this->lookup->getSiteLinksForItem( $itemId );
			$this->cache->set( $cacheKey, $siteLinks, $this->cacheTimeout );
		}

		return $siteLinks;
	}

	/**
	 * @see SiteLinkLookup::getEntityIdForSiteLink
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getEntityIdForSiteLink( SiteLink $siteLink ) {
		return $this->getItemIdForLink(
			$siteLink->getSiteId(),
			$siteLink->getPageName()
		);
	}

	/**
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return string
	 */
	private function getItemIdForLinkCacheKey( $globalSiteId, $pageTitle ) {
		return 'wikibase-sitelinks-by-page:' . $globalSiteId . ':' . $pageTitle;
	}

	/**
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return string|null
	 */
	private function getAndCacheItemIdForLink( $globalSiteId, $pageTitle ) {
		$itemId = $this->lookup->getItemIdForLink( $globalSiteId, $pageTitle );
		if ( $itemId instanceof ItemId ) {
			$itemId = $itemId->getSerialization();
		}

		$this->cache->set(
			$this->getItemIdForLinkCacheKey( $globalSiteId, $pageTitle ),
			$itemId,
			$this->cacheTimeout
		);

		return $itemId;
	}
}
