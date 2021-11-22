<?php

namespace Wikibase\Lib\Store;

use BagOStuff;

/**
 * PropertyOrderProvider implementation, that caches the information
 *
 * @license GPL-2.0-or-later
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
		$cached = $this->cache->getWithSetCallback(
			$this->cache->makeKey( 'wikibase-PropertyOrderProvider' ),
			$this->cacheDuration,
			function () {
				$propertyOrder = $this->propertyOrderProvider->getPropertyOrder();
				if ( $propertyOrder !== null ) {
					return $propertyOrder;
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
