<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use InvalidArgumentException;
use MapCacheLRU;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * A utility service for caching terms efficiently.
 *
 * The TermCache interacts with a persistent cache (such as memcached) and
 * also uses an in-process buffer (LRU hash) for pre-fetched data.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermCache {

	const USE_LOCAL_BUFFER = 0x1;

	const USE_PERSISTENT_CACHE = 0x2;

	/**
	 * @var array
	 */
	private $types;

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
	 * @var int $cacheTimeout
	 */
	private $cacheTimeout;

	/**
	 * The key prefix to use when caching entities in memory.
	 *
	 * @var $cacheKeyPrefix
	 */
	private $cacheKeyPrefix;

	/**
	 * @param string[] $types
	 * @param BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds. Defaults to 3600 (1 hour).
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbterms". There should be no reason to change this.
	 * @param int $bufferSize
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		array $types,
		BagOStuff $cache,
		$cacheDuration = 3600,
		$cacheKeyPrefix = 'wbterms',
		$bufferSize = 1000
	) {
		if ( count( array_diff( $types, array( 'labels', 'descriptions', 'aliases' ) ) ) ) {
			throw new InvalidArgumentException( '$types must only contain "labels", "descriptions", and/or "aliases"' );
		}

		$this->types = array_flip( $types );

		$this->persistentCache = $cache;
		$this->cacheTimeout = $cacheDuration;
		$this->cacheKeyPrefix = $cacheKeyPrefix;

		$this->buffer = new MapCacheLRU( $bufferSize );
	}

	/**
	 * Returns a cache key suitable for the given entity
	 *
	 * @param EntityId $entityId
	 * @param string|null $termType
	 * @param string|null $language
	 *
	 * @return string
	 */
	private function getCacheKey( EntityId $entityId, $termType = null, $language = null ) {
		$cacheKey = $this->cacheKeyPrefix . ':' . $entityId->getSerialization();

		if ( $termType !== null ) {
			$cacheKey .= ',' . $termType;
		}

		if ( $language !== null ) {
			$cacheKey .= ',' . $language;
		}

		return $cacheKey;
	}

	private function getLanguageGroupString( $name, array $languages ) {
		return $name . ':' . implode( '|', $languages );
	}

	private function getFingerprintInfoString( Fingerprint $terms ) {
		$info = '';

		if ( isset( $this->types['labels'] )  ) {
			$labelLanguages = array_keys( $terms->getLabels()->toTextArray() );
			$info .= $this->getLanguageGroupString( 'L', $labelLanguages ) . ';';
		}

		if ( isset( $this->types['descriptions'] )  ) {
			$descriptionLanguages = array_keys( $terms->getDescriptions()->toTextArray() );
			$info .= $this->getLanguageGroupString( 'D', $descriptionLanguages ) . ';';
		}

		if ( isset( $this->types['aliases'] )  ) {
			$aliasLanguages = array_keys( $terms->getAliasGroups()->toArray() );
			$info .= $this->getLanguageGroupString( 'A', $aliasLanguages ) . ';';
		}

		return $info;
	}

	private function getCacheValueBatch( EntityId $entityId, Fingerprint $terms ) {
		$batch = array(
			$this->getCacheKey( $entityId ) => $this->getFingerprintInfoString( $terms )
		);

		if ( isset( $this->types['labels'] )  ) {
			$entries = $this->getTermListEntries( $terms->getLabels() );
			$batch = array_merge( $batch, $entries );
		}

		if ( isset( $this->types['descriptions'] )  ) {
			$entries = $this->getTermListEntries( $terms->getDescriptions() );
			$batch = array_merge( $batch, $entries );
		}

		if ( isset( $this->types['aliases'] )  ) {
			$entries = $this->getTermGroupListEntries( $terms->getDescriptions() );
			$batch = array_merge( $batch, $entries );
		}

		return $batch;
	}

	private function getFingerprintCacheKeys( EntityId $entityId, $fingerprintInfo ) {
		$groups = explode( ';', $fingerprintInfo );
		$batch = array();

		foreach ( $groups as $group ) {
			list( $termType, $languages ) = explode( ':', $group, 2 );
			$languages = explode( '|', $languages );

			$batchForTermType = $this->getCacheKeyBatchForLanguages( $entityId, $termType, $languages );
			$batch = array_merge( $batch, $batchForTermType );
		}

		return $batch;
	}

	/**
	 * Update terms for the given entity.
	 * Any old terms associated with the entity are discarded.
	 **/
	public function updateEntityTerms( EntityId $entityId, Fingerprint $terms ) {
		$oldFingerprintInfo = $this->getCachedFingerprintInfo( $entityId );
		$oldCacheKeys = $this->getFingerprintCacheKeys( $entityId, $oldFingerprintInfo );

		$newCacheValues = $this->getCacheValueBatch( $entityId, $terms );

		//XXX: longer duration for fingerprint info? handle orphans?
		//XXX: randomized?
		$this->putValues( $newCacheValues );

		$obsoleteKeys = array_diff( $oldCacheKeys, array_keys( $newCacheValues ) );
		$this->deleteKeys( $obsoleteKeys );
	}


	/**
	 * Asserts that the given set of entities is present in the persistent cache.
	 * This does not prefetch any data into the local buffer.
	 *
	 * @todo XXX call this from the job queue, trigger (randomized) when uncached labels are requested.
	 *
	 * @param EntityId[] $entityIds
	 * @param TermLookup $termLookup (Note: make sure $termLookup doesn't use this TermCache!
	 * Otherwise, calling preloadEntityTerms will cause infinite regress).
	 */
	public function preloadEntityTerms( array $entityIds, TermLookup $termLookup ) {
		$entityIds = $this->getEntityIdsBySerialization( $entityIds );
		$entityIdsByKey = $this->getEntityIdsByCacheKeys( $entityIds );
		$entityKeys = array_keys( $entityIdsByKey );

		$cachedFingerprintInfo = $this->persistentCache->getMulti( $entityKeys );
		$missingKeys = array_diff( $entityKeys, array_keys( $cachedFingerprintInfo ) );
		$missingIds = array_diff_key( $entityIdsByKey, array_flip( $missingKeys ) );

		foreach ( $missingIds as $entityId ) {
			$fingerprint = $termLookup->getFingerprint( $entityId, $this->types );
			$this->cacheTermsForEntity( $entityId, $fingerprint );
		}
	}

	private function cacheTermsForEntity( EntityId $entityId, Fingerprint $terms ) {
		$newCacheValues = $this->getCacheValueBatch( $entityId, $terms );

		//XXX: longer duration for fingerprint info? handle orphans?
		//XXX: randomized?
		$this->putValues( $newCacheValues );
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
		$terms = array_zip( $languages, $terms );

		return $terms;
	}
}
