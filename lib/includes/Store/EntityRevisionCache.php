<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service for caching the latest EntityRevision of an Entity.
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class EntityRevisionCache {

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
	 * The key prefix to use when caching entities in memory.
	 *
	 * @var string
	 */
	private $cacheKeyPrefix;

	/**
	 * @param BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds. Defaults to 3600 (1 hour).
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbentity". There should be no reason to change this.
	 */
	public function __construct(
		BagOStuff $cache,
		$cacheDuration = 3600,
		$cacheKeyPrefix = 'wbentity'
	) {
		$this->cache = $cache;
		$this->cacheTimeout = $cacheDuration;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	/**
	 * Returns a cache key suitable for the given entity
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getCacheKey( EntityId $entityId ) {
		$cacheKey = $this->cacheKeyPrefix . ':' . $entityId->getSerialization();

		return $cacheKey;
	}

	/**
	 * Get the latest EntityRevision from cache.
	 * Note: This might return stale data!
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 * @return EntityRevision|null Null if the EntityRevision is not cached.
	 */
	public function getEntityRevision( EntityId $entityId ) {
		$key = $this->getCacheKey( $entityId );

		/** @var EntityRevision $entityRevision */
		$entityRevision = $this->cache->get( $key );

		if ( $entityRevision === false ) {
			$entityRevision = null;
		}

		return $entityRevision;
	}

	/**
	 * Place the latest EntityRevision in the cache.
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function putEntityRevision( EntityRevision $entityRevision ) {
		$key = $this->getCacheKey( $entityRevision->getEntity()->getId() );
		$this->cache->set( $key, $entityRevision, $this->cacheTimeout );
	}

	/**
	 * Removes an Entity's EntityRevision from the cache.
	 *
	 * @param EntityId $entityId
	 */
	public function removeEntityRevision( EntityId $entityId ) {
		$key = $this->getCacheKey( $entityId );
		$this->cache->delete( $key );
	}

}
