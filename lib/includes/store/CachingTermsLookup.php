<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermList;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CachingTermsLookup implements TermsLookup {

	/**
	 * @param TermsLookup $termsLookup Used as fallback for cache misses.
	 * @param BagOStuff $cache
	 * @param int $cacheDuration Cache duration in seconds. Defaults to 86400 (1 day).
	 * @param string $cacheKeyPrefix Defaults to 'wikibase'.
	 */
	public function __construct(
		TermsLookup $termsLookup,
		BagOStuff $cache,
		$cacheDuration = 86400,
		$cacheKeyPrefix = 'wikibase'
	) {
		$this->termsLookup = $termsLookup;
		$this->cache = $cache;
		$this->cacheTimeout = $cacheDuration;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 *
	 * @return string[]
	 */
	public function getTermsByTermType( EntityId $entityId, $termType ) {
		$cacheKey = $this->getCacheKey( $entityId, $termType );

		$terms = $this->cache->get( $cacheKey );

		if ( !is_array( $terms ) ) {
			$terms = $this->termsLookup->getTermsByTermType( $entityId, $termType );
			$this->cache->set( $cacheKey, $terms );
		}

		return $terms;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 */
	private function getCacheKey( EntityId $entityId, $termType ) {
		return $this->cacheKeyPrefix . ':' . $entityId->getSerialization() . ":$termType";
	}

	/**
	 * @param EntityId $entityId
	 */
	public function invalidateCacheForEntityId( EntityId $entityId ) {
		$termTypes = array( 'label', 'description' );

		foreach( $termTypes as $termType ) {
			$cacheKey = $this->getCacheKey( $entityId, $termType );
			$this->cache->delete( $cacheKey );
		}
	}

}
