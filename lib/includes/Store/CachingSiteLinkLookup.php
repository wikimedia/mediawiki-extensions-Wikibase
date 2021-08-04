<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * SiteLinkLookup implementation that caches the obtained data (except for data obtained
 * via "getLinks").
 * Note: This doesn't implement any means of purging or data invalidation beyond the cache
 * timeout.
 *
 * @license GPL-2.0-or-later
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
	private $cacheDuration;

	/**
	 * @var string
	 */
	private $cacheKeyPrefix;

	/**
	 * @param SiteLinkLookup $siteLinkLookup The lookup to use
	 * @param BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds. Defaults to 3600 (1 hour).
	 * @param string $cacheKeyPrefix Cache key prefix to use.
	 *     Important in case we're not in-process caching. Defaults to "wikibase"
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		BagOStuff $cache,
		$cacheDuration = 3600,
		$cacheKeyPrefix = 'wikibase'
	) {
		$this->lookup = $siteLinkLookup;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	public function getItemIdForLink( string $globalSiteId, string $pageTitle ): ?ItemId {
		$itemIdSerialization = $this->cache->get( $this->getByPageCacheKey( $globalSiteId, $pageTitle ) );

		if ( is_string( $itemIdSerialization ) ) {
			return new ItemId( $itemIdSerialization );
		} elseif ( $itemIdSerialization === false ) {
			return $this->getAndCacheItemIdForLink( $globalSiteId, $pageTitle );
		}

		return null;
	}

	/**
	 * This is uncached!
	 */
	public function getLinks(
		?array $numericIds = null,
		?array $siteIds = null,
		?array $pageNames = null
	): array {
		// Caching this would be rather complicated for little to no benefit.
		return $this->lookup->getLinks( $numericIds, $siteIds, $pageNames );
	}

	/**
	 * Returns an array of SiteLink objects for an item. If the item isn't known or not an Item,
	 * an empty array is returned.
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinksForItem( ItemId $itemId ): array {
		$cacheKey = $this->cacheKeyPrefix . ':sitelinks:' . $itemId->getSerialization();
		$siteLinks = $this->cache->get( $cacheKey );

		if ( !is_array( $siteLinks ) ) {
			$siteLinks = $this->lookup->getSiteLinksForItem( $itemId );
			$this->cache->set( $cacheKey, $siteLinks, $this->cacheDuration );
		}

		return $siteLinks;
	}

	public function getItemIdForSiteLink( SiteLink $siteLink ): ?ItemId {
		return $this->getItemIdForLink(
			$siteLink->getSiteId(),
			$siteLink->getPageName()
		);
	}

	private function getByPageCacheKey( string $globalSiteId, string $pageTitle ): string {
		return $this->cacheKeyPrefix . ':sitelinks-by-page:' . $globalSiteId . ':' . $pageTitle;
	}

	private function getAndCacheItemIdForLink( string $globalSiteId, string $pageTitle ): ?ItemId {
		$itemId = $this->lookup->getItemIdForLink( $globalSiteId, $pageTitle );

		$this->cache->set(
			$this->getByPageCacheKey( $globalSiteId, $pageTitle ),
			$itemId instanceof ItemId ? $itemId->getSerialization() : null,
			$this->cacheDuration
		);

		return $itemId;
	}

}
