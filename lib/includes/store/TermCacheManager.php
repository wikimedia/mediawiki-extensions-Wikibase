<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use MapCacheLRU;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
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
 * The implementation caches an "inventory" for each entity. The inventory contains the
 * information which terms are defined for a given entity. This allows cached terms to be
 * purged efficiently, and it also allows easy negative caching.
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
	const UNDEFINED = "\0";

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
	 * @param EntityIdParser $idParser
	 * @param BagOStuff $cache The cache to use
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbterms". There should be no reason to change this.
	 * @param int $bufferSize
	 */
	public function __construct(
		EntityIdParser $idParser,
		BagOStuff $cache,
		$cacheKeyPrefix = 'wbterms',
		$bufferSize = 1000
	) {
		$this->persistentCache = $cache;

		//FIXME: pass a list of languages. If the cache is shared between many wikis with
		// different languages, we want to fetch all. If we only use the cache locally,
		// we may not want to cache all languages (unless perhaps the requested language
		// is determined by user preferences).
		$this->cacheCodec = new TermCacheCodec( $idParser, $cacheKeyPrefix );

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
		$inventoryKey = $this->cacheCodec->getInventoryKey( $entityId );
		$oldInventory = $this->persistentCache->get( $inventoryKey );
		$oldCacheKeys = $this->cacheCodec->getCacheKeysForInventory( $entityId, $oldInventory );

		$newCacheValues = $this->cacheCodec->getCacheValues( $entityId, $terms );

		$this->setCachedValue( $inventoryKey, $this->cacheCodec->getInventoryString( $terms ), $this->cacheDuration * 2 );
		$this->setCachedValues( $newCacheValues, $this->cacheDuration );

		$obsoleteKeys = array_diff( $oldCacheKeys, array_keys( $newCacheValues ) );
		$this->deleteCachedValues( $obsoleteKeys );
	}

	private function setCachedValues( array $values, $duration ) {
		$this->persistentCache->setMulti( $values, $duration );
		$this->setBufferedValues( $values );
	}

	private function setCachedValue( $key, $value, $duration ) {
		$this->persistentCache->set( $key, $value, $duration );
		$this->setBufferedValue( $key, $value );
	}

	private function getCachedValues( array $keys ) {
		return $this->persistentCache->getMulti( $keys );
	}

	private function getCachedValue( $key ) {
		$value = $this->persistentCache->get( $key );

		// BagOStuff returns false when an entry is not found. Annoying.
		return $value === false ? null : $value;
	}

	private function deleteCachedValues( array $keys ) {
		foreach ( $keys as $key ) {
			// TODO: use native multi-delete if available
			$this->persistentCache->delete( $key );
		}

		$this->deleteBufferedValues( $keys );
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

	private function setBufferedValue( $key, $value ) {
		$this->buffer->set( $key, $value );
	}

	private function setBufferedValues( array $values ) {
		foreach ( $values as $key => $value ) {
			$this->buffer->set( $key, $value );
		}
	}

	private function deleteBufferedValues( array $keys ) {
		$this->buffer->clear( $keys );
	}

	private function stripUndefined( $values ) {
		return array_filter(
			$values,
			function ( $v ) {
				return $v !== TermCacheManager::UNDEFINED;
			}
		);
	}

	/**
	 * Determines what calls to TermLookup::getFingerprint are needed to fetch the terms
	 * associated with the given keys. Each call is represented as an array( $entityId, $types, $languages )
	 *
	 * @param string[] $keys
	 *
	 * @return array[] A list of triples list( $entityId, $types, $languages )
	 */
	private function getInfoLookupCallsForCacheKeys( array $keys ) {
		$calls = array();

		foreach ( $keys as $key ) {
			list( $entityId, $termType, $language ) = $this->cacheCodec->parseCacheKey( $key );
			$entityKey = $entityId->getSerialization();

			if ( !isset( $calls[$entityKey] ) ) {
				$calls[$entityKey] = array(
					$entityId,
					array( $termType ),
					array( $language ),
				);
			} else {
				$calls[$entityKey][1][] = $termType;
				$calls[$entityKey][2][] = $language;
			}
		}

		return $calls;
	}

	private function fetchTermsForCacheKeys( array $keys, TermLookup $termLookup, $preloadRandomizationFactor = 0 ) {
		$missingEntityIds = array();
		$lookupCalls = $this->getInfoLookupCallsForCacheKeys( $keys );
		foreach ( $lookupCalls as $call ) {
			list( $entityId, $types, $languages ) = $call;
			$terms = $this->fetchFingerprint( $entityId, $types, $languages, $termLookup );

			$missingEntityIds[] = $entityId;
		}

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

		// The cache keys we want are the keys for the inventories for the entities,
		// plus the keys for the terms specified by $entityIds, $termTypes, and $languages.
		$inventoryKeys = $this->cacheCodec->getInventoryKeys( $entityIds );
		$termKeys = $this->cacheCodec->getCacheKeys( $entityIds, $termTypes, $languages );
		$remainingKeys = array_merge( $inventoryKeys, $termKeys);

		// See which terms and inventories are in the local buffer.
		// Remember which keys remain unresolved.
		$bufferedValues = $this->getBufferedValues( $remainingKeys );
		$remainingKeys = array_diff( $remainingKeys, array_keys( $bufferedValues ) );

		// Get remaining terms and inventories from the persistent cache.
		// Put them into the local buffer and remember which keys remain unresolved.
		$cachedValues = $this->getCachedValues( $remainingKeys );
		$this->setBufferedValues( $cachedValues );
		$remainingKeys = array_diff( $remainingKeys, array_keys( $cachedValues ) );

		// Use the list of inventories we got so far to check which of the remaining
		// keys refer to terms that are not actually defined on the entities,
		// and mark them as UNDEFINED in the local buffer. Remember which keys remain
		// to be checked.
		$inventories = array_intersect_key(
			array_merge( $bufferedValues, $cachedValues ),
			array_flip( $inventoryKeys )
		);

		$undefinedValues = $this->getUndefinedValues( $remainingKeys, $inventories );
		$this->setBufferedValues( $undefinedValues );
		$remainingKeys = array_diff( $remainingKeys, array_keys( $undefinedValues ) );

		// For the remaining keys, fetch them from the TermLookup and put them into
		// the local buffer (but not into the persistent cache).
		// Remember which keys still could not be resolved.
		$remainingTermKeys = array_intersect_key( $termKeys, $remainingKeys );
		$fetchedValues = $this->fetchTermsForCacheKeys( $termLookup, $remainingTermKeys );
		$this->setBufferedValues( $fetchedValues );


		// Term keys that could not be resolved via the TermLookup can be marked as undefined,
		// so we don't try them again.
		$remainingTermKeys = array_diff( $remainingTermKeys, array_keys( $fetchedValues ) );
		$undefinedValues = $this->makeUndefinedValues( $remainingTermKeys );
		$this->setBufferedValues( $undefinedValues );

		// The entities we loaded terms for in the last step are the ones we may want to
		// schedule a fill re-cache for. This is especially useful if teh cache is shared.
		$entitysToRecache = $this->cacheCodec->getEntityIdsFromKeys( array_keys( $fetchedValues ) );

		//FIXME: randomized?
		if ( $this->cacheRandomization && rand( 0, $this->cacheRandomization ) === 0 ) {
			$this->scheduleRecache( $entitysToRecache );
		}

		// We could return the terms here, but collecting them into Fingerprints is overhead,
		// and we generally don't need them.
	}

	private function fetchFingerprint( EntityId $entityId, array $types, array $languages, TermLookup $termLookup ) {
		$fingerprint = Fingerprint::newEmpty();

		if ( in_array( 'labels', $types ) ) {
			$labels = $termLookup->getLabels( $entityId, $languages );
			$fingerprint->setLabels( $this->makeTermList( $labels ) );
		}

		if ( in_array( 'descriptions', $types ) ) {
			$descriptions = $termLookup->getDescriptions( $entityId, $languages );
			$fingerprint->setDescriptions( $this->makeTermList( $descriptions ) );
		}

		return $fingerprint;
	}

	/**
	 * @param string[] $strings keys by language
	 *
	 * @return TermList
	 */
	public function makeTermList( array $strings ) {
		$terms = new TermList();

		foreach ( $strings as $languageCode => $string ) {
			$terms->setTerm( new Term( $languageCode, $string ) );
		}

		return $terms;
	}

	/**
	 * Get the requested terms from the cache.
	 *
	 * @param EntityId[] $entityId
	 * @param string $termType
	 * @param string[] $languages
	 * @param int $mode bitmap, use the USE_XXX constants.
	 *
	 * @return string[] any terms found in the cache, keyed by language.
	 */
	public function getCachedTerms( array $entityId, $termType, array $languages, $mode = self::USE_LOCAL_BUFFER ) {
		$keys = $allKeys = $this->cacheCodec->getCacheKeys( array( $entityId ), array( $termType ), $languages );
		$terms = array();

		if ( $mode & self::USE_LOCAL_BUFFER ) {
			$terms = $this->getBufferedValues( $keys );
			$keys = array_diff( $keys, array_keys( $terms ) );
		}

		if ( !empty( $keys ) && $mode & self::USE_PERSISTENT_CACHE ) {
			$cachedValues = $this->getCachedValues( $keys );

			if ( $mode & self::USE_LOCAL_BUFFER ) {
				$this->setBufferedValues( $cachedValues );
			}

			$terms = array_merge( $terms, $cachedValues );
		}

		$terms = $this->stripUndefined( $terms );
		$terms = $this->cacheCodec->convertKeysToLanguageCodes( $terms );

		return $terms;
	}

}
