<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use BagOStuff;

/**
 * ItemOrderProvider implementation, that caches the information
 *
 * @license GPL-2.0-or-later
 * @author Noa Rave
 */
class CachingItemOrderProvider implements ItemOrderProvider {

	/**
	 * @var ItemOrderProvider
	 */
	private $itemOrderProvider;

	/**
	 * The cache to use for caching the Item
	 * order.
	 *
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * The cache key under which the cached
	 * item order will be stored.
	 *
	 * @var string
	 */
	private $cacheKey;

	/**
	 * @var int
	 */
	private $cacheDuration;

	public function __construct(
		ItemOrderProvider $itemOrderProvider,
		BagOStuff $cache,
		string $cacheKey,
		int $cacheDuration = 3600
	) {
		$this->itemOrderProvider = $itemOrderProvider;
		$this->cache = $cache;
		$this->cacheKey = $cacheKey;
		$this->cacheDuration = $cacheDuration;
	}

	/**
	 * @see ItemOrderProvider::getItemOrder
	 * @return int[]|null
	 */
	public function getItemOrder(): ?array {
		$cached = $this->cache->getWithSetCallback(
			$this->cache->makeKey( $this->cacheKey ),
			$this->cacheDuration,
			function () {
				$itemOrder = $this->itemOrderProvider->getItemOrder();
				if ( $itemOrder !== null ) {
					return $itemOrder;
				} else {
					return false;
				}
			}
		);
		if ( $cached !== false ) {
			return $cached;
		} else {
			return null;
		}
	}

}
