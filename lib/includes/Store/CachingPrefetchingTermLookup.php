<?php

namespace Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class CachingPrefetchingTermLookup implements PrefetchingTermLookup {

	use TermCacheKeyBuilder;

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @var UncachedTermsPrefetcher
	 */
	private $termsPrefetcher;

	/**
	 * @var RedirectResolvingLatestRevisionLookup
	 */
	private $redirectResolvingRevisionLookup;

	public function __construct(
		CacheInterface $cache,
		UncachedTermsPrefetcher $termsPrefetcher,
		RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup
	) {
		$this->cache = $cache;
		$this->termsPrefetcher = $termsPrefetcher;
		$this->redirectResolvingRevisionLookup = $redirectResolvingRevisionLookup;
	}

	public function prefetchTerms( array $entityIds, array $termTypes, array $languageCodes ) {
		$this->termsPrefetcher->prefetchUncached( $this->cache, $entityIds, $termTypes, $languageCodes );
	}

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$cacheEntry = $this->getCacheEntry( $entityId, $termType, $languageCode );
		return $termType === 'alias' && $cacheEntry !== null ? $cacheEntry[0] : $cacheEntry;
	}

	public function getLabel( EntityId $entityId, $languageCode ) {
		return $this->getCacheEntry( $entityId, 'label', $languageCode );
	}

	public function getLabels( EntityId $entityId, array $languageCodes ) {
		return $this->getMultipleTermsByLanguage( $entityId, 'label', $languageCodes );
	}

	public function getDescription( EntityId $entityId, $languageCode ) {
		return $this->getCacheEntry( $entityId, 'description', $languageCode );
	}

	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		return $this->getMultipleTermsByLanguage( $entityId, 'description', $languageCodes );
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		return $this->getCacheEntry( $entityId, 'alias', $languageCode );
	}

	private function getCacheKey( EntityId $id, string $language, string $termType ) {
		$resolutionResult = $this->redirectResolvingRevisionLookup->lookupLatestRevisionResolvingRedirect( $id );
		if ( $resolutionResult === null ) {
			return null;
		}

		return $this->buildCacheKey( $resolutionResult[1], $resolutionResult[0], $language, $termType );
	}

	private function getCacheEntry( EntityId $entityId, string $termType, string $languageCode ) {
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termType );
		return $cacheKey === null ? null : $this->cache->get( $cacheKey );
	}

	private function getMultipleTermsByLanguage( EntityId $entityId, string $termType, array $languages ) {
		$languagesToCacheKeys = [];

		foreach ( $languages as $language ) {
			$cacheKey = $this->getCacheKey( $entityId, $language, $termType );
			if ( $cacheKey ) {
				$languagesToCacheKeys[$language] = $cacheKey;
			}
		}

		$cacheKeysToLanguages = array_flip( $languagesToCacheKeys );
		$cacheEntries = $this->cache->getMultiple( array_values( $languagesToCacheKeys ) );
		$terms = [];

		foreach ( $cacheEntries as $key => $term ) {
			$terms[$cacheKeysToLanguages[$key]] = $term;
		}

		return $terms;
	}

}
