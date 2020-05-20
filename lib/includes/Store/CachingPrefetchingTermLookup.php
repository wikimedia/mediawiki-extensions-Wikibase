<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;

/**
 * Prefetches terms from the UncachedTermsPrefetcher which stores the terms in the CacheInterface.
 * Looks up terms from CacheInterface.
 *
 * For this reason CacheInterface MUST be a real cache (can store for at least this 1 request).
 *
 * CacheInterface determines the medium of caching, and thus the availability (process, server, WAN).
 * UncachedTermsPrefetcher controls the ttl of the cached data.
 *
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

	/**
	 * @var ContentLanguages
	 */
	private $termLanguages;

	/**
	 * @param CacheInterface $cache This must actually be a functioning cache, that can at least cache things within the current request.
	 * This is because the UncachedTermsPrefetcher uses the cache to pass data back to this class.
	 * If you do not have a cache you should be using PrefetchingPropertyTermLookup directly.
	 * @param UncachedTermsPrefetcher $termsPrefetcher
	 * @param RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup
	 * @param ContentLanguages $termLanguages
	 */
	public function __construct(
		CacheInterface $cache,
		UncachedTermsPrefetcher $termsPrefetcher,
		RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup,
		ContentLanguages $termLanguages
	) {
		$this->cache = $cache;
		$this->termsPrefetcher = $termsPrefetcher;
		$this->redirectResolvingRevisionLookup = $redirectResolvingRevisionLookup;
		$this->termLanguages = $termLanguages;
	}

	public function prefetchTerms( array $entityIds, array $termTypes, array $languageCodes ) {
		$this->termsPrefetcher->prefetchUncached(
			$this->cache,
			$entityIds,
			$termTypes,
			$this->filterValidTermLanguages( $languageCodes )
		);
	}

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$cacheEntry = $this->getCacheEntry( $entityId, $termType, $languageCode );
		return $termType === 'alias' && $cacheEntry !== null ? $cacheEntry[0] : $cacheEntry;
	}

	public function getLabel( EntityId $entityId, $languageCode ) {
		return $this->getCacheEntry( $entityId, TermTypes::TYPE_LABEL, $languageCode );
	}

	public function getLabels( EntityId $entityId, array $languageCodes ) {
		return $this->getMultipleTermsByLanguage( $entityId, TermTypes::TYPE_LABEL, $languageCodes );
	}

	public function getDescription( EntityId $entityId, $languageCode ) {
		return $this->getCacheEntry( $entityId, TermTypes::TYPE_DESCRIPTION, $languageCode );
	}

	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		return $this->getMultipleTermsByLanguage( $entityId, TermTypes::TYPE_DESCRIPTION, $languageCodes );
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		return $this->getCacheEntry( $entityId, TermTypes::TYPE_ALIAS, $languageCode );
	}

	private function getCacheKey( EntityId $id, string $language, string $termType ) {
		$resolutionResult = $this->redirectResolvingRevisionLookup->lookupLatestRevisionResolvingRedirect( $id );
		if ( $resolutionResult === null ) {
			return null;
		}

		return $this->buildCacheKey( $resolutionResult[1], $resolutionResult[0], $language, $termType );
	}

	private function getCacheEntry( EntityId $entityId, string $termType, string $languageCode ) {
		if ( !$this->termLanguages->hasLanguage( $languageCode ) ) {
			return null;
		}
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termType );
		return $cacheKey === null ? null : $this->cache->get( $cacheKey );
	}

	private function getMultipleTermsByLanguage( EntityId $entityId, string $termType, array $languages ) {
		$languagesToCacheKeys = [];

		foreach ( $this->filterValidTermLanguages( $languages ) as $language ) {
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

	private function filterValidTermLanguages( array $languageCodes ): array {
		return array_filter(
			$languageCodes,
			function ( $languageCode ) {
				return $this->termLanguages->hasLanguage( $languageCode );
			}
		);
	}

}
