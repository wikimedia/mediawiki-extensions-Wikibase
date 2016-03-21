<?php

namespace Wikibase\Lib\Store;

/**
 * Interface for the MediaWikiPagePropertyOrderProvider
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
	private $cacheKeyPrefix;

	/**
	 *
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
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	public function getPropertyOrder() {
		$propertyOrder = $this->propertyOrderProvider->getPropertyOrder();
		$this->cache->set(
			null,
			$propertyOrder,
			$this->cacheDuration
		);
	}
}

