<?php

namespace Wikibase\Lib\Store;

/**
 * Interface for the WikiPagePropertyOrderProvider
 *
 * @license GNU GPL v2+
 * @author Lucie-Aimée Kaffee
 */

class CachingPropertyOrderProvider implements PropertyOrderProvider {

	/**
	 * @var PropertyOrderProvider
	 */
	private $propertyOrderProvider;

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
	private $cacheKey;

	/**
	 * @param PropertyOrderProvider $propertyOrderProvider
	 * @param BagOStuff $cache
	 * @param int $cacheDuration
	 * @param string $cacheKeyPrefix
	 */
	public function __construct(
		PropertyOrderProvider $propertyOrderProvider,
		BagOStuff $cache,
		$cacheDuration = 3600,
		$cacheKeyPrefix = 'wikibase'
	) {
		$this->propertyOrderProvider = $propertyOrderProvider;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;
		$this->cacheKey = $cacheKey;
	}

	/**
	 * @return int[]|null
	 */
	public function getPropertyOrder() {
		//check if the list is already in the cach
		$propertyOrder  = $this->cache->get( $this->cacheKey );
		if ( $propertyOrder !== false ) {
			return $propertyOrder;
		}

		// if not, add it to the cache
		$propertyOrder = $this->propertyOrderProvider->getPropertyOrder();
		if( $propertyOrder !== null ) {
			$this->cache->set(
				$this->cacheKey,
				$propertyOrder,
				$this->cacheDuration
			);
		}
		return $propertyOrder;
	}
}

