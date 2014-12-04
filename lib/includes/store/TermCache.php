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

	/**
	 * @var array
	 */
	private $types;

	/**
	 * The cache to use for caching entities.
	 *
	 * @var BagOStuff
	 */
	private $cache;

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

		$this->cache = $cache;
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

		//FIXME: use double duration for meta cache key
		$this->putValues( $newCacheValues );

		$obsoleteKeys = array_diff( $oldCacheKeys, array_keys( $newCacheValues ) );
		$this->deleteKeys( $obsoleteKeys );
	}


	/**
	 * Loads a set of terms into memory, for later use by getTerms().
	 *
	 * @param EntityId[] $entityId
	 */
	public function prefetchEntityTermCache( array $entityId, array $termTypes, array $languages ) {

	}

	/**
	 * Loads a set of terms into memory, for later use by getTerms().
	 *
	 * @param EntityId[] $entityId
	 */
	public function prefetchTerms( array $entityId, array $termTypes, array $languages ) {
		$keys = $this->getPrefetchKeyBatch( $entityId, $termTypes, $languages );

		$bufferedValues = $this->getBufferedValues( $keys );
		$keys = array_diff( $keys, array_keys( $bufferedValues ) );

		$cachedValues = $this->getValues( $keys );
		$this->putBufferedValues( $cachedValues );
		$keys = array_diff( $keys, array_keys( $cachedValues ) );

		// Find out which $entityIds need fetching and caching.
		// Return a lit of EntityIds that were handled (or not handled?)
	}

	/**
	 * Get terms of the given types for the given entities,
	 * in the given languages (or all languages).
	 *
	 * @param EntityId[] $entityIds
	 * @param string $termTypes
	 * @param string $languages
	 *
	 * @internal param $EntityId[]
	 */
	public function getTerms( array $entityIds, $termTypes, $languages ) {

	}

}
