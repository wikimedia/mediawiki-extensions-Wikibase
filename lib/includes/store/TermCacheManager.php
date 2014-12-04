<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use InvalidArgumentException;
use MapCacheLRU;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * A utility service for caching terms efficiently.
 *
 * The TermCacheManager interacts with a persistent cache (such as memcached) and
 * also uses an in-process buffer (LRU hash). It implements the following behavior:
 *
 * - updateEntityTerms() is intended to be called when an entity changes. It will push
 * the terms for the entity to the persistent cache, and remove any obsolete terms cached
 * for that entity. The idea is that changing an entity triggers page updates, which will
 * profit from the respective terms being in the cache.
 *
 * - fetchTerms() loads a batch of terms into the local buffer, loading them from the
 * persistent cache or, if that fails, from a TermLookup. When loading terms from the
 * TermLookup, all languages are loaded and, with a certain probability, pushed into
 * the persistent cache.
 *
 * - getCachedTerms() looks up terms in the local buffer or, if so requested, from the
 * persistent cache. Cache misses may, with a certain probability, cause a Job to
 * be scheduled for pushing the missing entity to the cache.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermCacheManager {

	/**
	 * Bitmap flag indicating that the local buffer is to be used.
	 */
	const USE_LOCAL_BUFFER = 0x1;

	/**
	 * Bitmap flag indicating that the persistent cache is to be used.
	 */
	const USE_PERSISTENT_CACHE = 0x2;

	/**
	 * Cache value for negative caching.
	 */
	const MISSING = "\0";

	/**
	 * The cache to use for caching entities.
	 *
	 * @var BagOStuff
	 */
	private $persistentCache;

	/**
	 * The cache to use for caching entities.
	 *
	 * @var MapCacheLRU
	 */
	private $buffer;

	/**
	 * @var int
	 */
	private $cacheDuration = 86400; // 60 * 60 * 24

	/**
	 * Cache codec utility
	 *
	 * @var TermCacheCodec
	 */
	private $cacheCodec;

	/**
	 * @var int
	 */
	private $cacheRandomization = 1;

	/**
	 * @param BagOStuff $cache The cache to use
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbterms". There should be no reason to change this.
	 * @param int $bufferSize
	 */
	public function __construct(
		BagOStuff $cache,
		$cacheKeyPrefix = 'wbterms',
		$bufferSize = 1000
	) {
		$this->persistentCache = $cache;
		$this->cacheCodec = new TermCacheCodec( $cacheKeyPrefix );

		$this->buffer = new MapCacheLRU( $bufferSize );
	}

	/**
	 * @param int $cacheRandomization
	 */
	public function setCacheRandomization( $cacheRandomization ) {
		$this->cacheRandomization = $cacheRandomization;
	}

	/**
	 * @return int
	 */
	public function getCacheRandomization() {
		return $this->cacheRandomization;
	}

	/**
	 * @param int $cacheDuration
	 */
	public function setCacheDuration( $cacheDuration ) {
		$this->cacheDuration = $cacheDuration;
	}

	/**
	 * @return int
	 */
	public function getCacheDuration() {
		return $this->cacheDuration;
	}

	/**
	 * Update terms for the given entity.
	 * Any old terms associated with the entity are discarded.
	 **/
	public function updateEntityTerms( EntityId $entityId, Fingerprint $terms ) {
		$inventoryKey = $this->cacheCodec->getCacheKey( $entityId );
		$oldInventory = $this->persistentCache->get( $inventoryKey );
		$oldCacheKeys = $this->cacheCodec->getCacheKeysForInventory( $entityId, $oldInventory );

		$newCacheValues = $this->cacheCodec->getCacheValues( $entityId, $terms );

		//XXX: longer duration for fingerprint info? handle orphans?
		//XXX: randomized?
		$this->putValue( $inventoryKey, $this->cacheCodec->getInventoryString( $terms ), $this->cacheDuration * 2 );
		$this->putValues( $newCacheValues, $this->cacheDuration );

		$obsoleteKeys = array_diff( $oldCacheKeys, array_keys( $newCacheValues ) );
		$this->deleteKeys( $obsoleteKeys );
	}

	private function putValues( array $values, $duration ) {
		$this->persistentCache->setMulti( $values, $duration );
		$this->setBufferedValues( $values );
	}

	private function putValue( $key, $value, $duration ) {
		$this->persistentCache->set( $key, $value, $duration );
		$this->setBufferedValues( array( $key => $value ) );
	}

	private function deleteKeys( array $keys ) {
		foreach ( $keys as $key ) {
			// TODO: use native multi-delete if available
			$this->persistentCache->delete( $key );
		}

		$this->deleteBufferedKeys( $keys );
	}

	private function getBufferedValues( array $keys ) {
		$values = array();

		foreach ( $keys as $key ) {
			if ( $this->buffer->has( $key ) ) {
				$values[] = $this->buffer->get( $key );
			}
		}

		return $values;
	}

	private function setBufferedValues( array $values ) {
		$values = array();

		foreach ( $values as $key => $value ) {
			$values[] = $this->buffer->set( $key );
		}

		return $values;
	}

	/**
	 * Loads a set of terms into memory, for later use by getTerms().
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $termTypes
	 * @param string[] $languages
	 * @param TermLookup $termLookup
	 */
	public function fetchTerms( array $entityIds, array $termTypes, array $languages, TermLookup $termLookup ) {
		$keys = $this->getCacheKeys( $entityIds, $termTypes, $languages );

		$bufferedValues = $this->getBufferedValues( $keys );
		$keys = array_diff( $keys, array_keys( $bufferedValues ) );

		$cachedValues = $this->getCachedValues( $keys );
		$this->putBufferedValues( $cachedValues );
		$keys = array_diff( $keys, array_keys( $cachedValues ) );

		$missingEntityIds = array();
		$lookupCalls = $this->parseCacheKeysInfoLookupCalls( $keys );
		foreach ( $lookupCalls as $call ) {
			list( $entityId, $types, $languages ) = $call;
			$terms = $termLookup->getFingerprint( $entityId, $types, $languages );
			$this->putBufferedFingerprint( $terms );
			$missingEntityIds[] = $entityId;
		}

		//TODO: push preloadEntityTerms() to job queue
		//XXX: randomized?
		$this->schedulePreload( $missingEntityIds );

		//XXX: we could return the terms here, but collecting them into Fingerprints is overhead,
		//     and we generally don't need them.
	}

	/**
	 * Get the requested terms from the cache.
	 *
	 * @param array|\Wikibase\DataModel\Entity\EntityId $entityId
	 * @param string $termType
	 * @param string[] $languages
	 * @param int $mode bitmap, use the USE_XXX constants.
	 *
	 * @return string[] any terms found in the cache, keyed by language.
	 */
	public function getCachedTerms( array $entityId, $termType, array $languages, $mode = self::USE_LOCAL_BUFFER ) {
		$keys = $allKeys = $this->getCacheKeys( array( $entityId ), array( $termType ), $languages );
		$terms = array();

		if ( $mode & self::USE_LOCAL_BUFFER ) {
			$terms = $this->getBufferedValues( $keys );
			$keys = array_diff( $keys, array_keys( $terms ) );
		}

		if ( !empty( $keys ) && $mode & self::USE_PERSISTENT_CACHE ) {
			$cachedValues = $this->getCachedValues( $keys );

			if ( $mode & self::USE_LOCAL_BUFFER ) {
				$this->putBufferedValues( $cachedValues );
			}

			$terms = array_merge( $terms, $cachedValues );
		}

		$languages = $this->convertKeysToLanguages( $allKeys );
		$terms = array_combine( $languages, $terms );

		return $terms;
	}
}
