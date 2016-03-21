<?php

namespace Wikibase\Lib\Store;

use BagOStuff;

/**
 * PropertyOrderProvider implementation, that caches the information
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
	 * The cache to use for caching the property order.
	 *
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @param PropertyOrderProvider $propertyOrderProvider
	 * @param BagOStuff $cache
	 * @param int $cacheDuration
	 */
	public function __construct(
		PropertyOrderProvider $propertyOrderProvider,
		BagOStuff $cache,
		$cacheDuration = 3600
	) {
		$this->propertyOrderProvider = $propertyOrderProvider;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;
	}

	/**
	 * @see PropertyOrderProvider::getPropertyOrder
	 * @return int[]|null
	 */
	public function getPropertyOrder() {
		$cacheKey = wfMemcKey( 'ArticlePlaceholder-PropertyOrderProvider' );

		// check if the list is already in the cache
		$propertyOrder = $this->cache->get( $cacheKey );
		if ( $propertyOrder !== false ) {
			return $propertyOrder;
		}

		// if not, add it to the cache
		$propertyOrder = $this->propertyOrderProvider->getPropertyOrder();

		if ( $propertyOrder !== null ) {
			$this->cache->set(
				$cacheKey,
				$propertyOrder,
				$this->cacheDuration
			);
		}
		return $propertyOrder;
	}

}
