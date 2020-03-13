<?php

namespace Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermTypes;

/**
 * Determines which requested terms are not cached, then fetches and caches them.
 *
 * @license GPL-2.0-or-later
 */
class UncachedTermsPrefetcher {

	use TermCacheKeyBuilder;

	private const DEFAULT_TTL = 60;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $lookup;

	/**
	 * @var RedirectResolvingLatestRevisionLookup
	 */
	private $redirectResolvingRevisionLookup;

	/**
	 * @var int
	 */
	private $cacheEntryTTL;

	public function __construct(
		PrefetchingTermLookup $lookup,
		RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup,
		?int $ttl = null
	) {
		$this->lookup = $lookup;
		$this->redirectResolvingRevisionLookup = $redirectResolvingRevisionLookup;
		$this->cacheEntryTTL = $ttl ?? self::DEFAULT_TTL;
	}

	public function prefetchUncached( CacheInterface $cache, array $entityIds, array $termTypes, array $languageCodes ) {
		$entitiesToPrefetch = [];
		$languagesToPrefetch = [];

		foreach ( $entityIds as $id ) {
			$uncachedLanguages = $this->getUncachedLanguagesForEntityTerms( $cache, $id, $termTypes, $languageCodes );

			if ( !empty( $uncachedLanguages ) ) {
				$entitiesToPrefetch[] = $id;
				$languagesToPrefetch = $languagesToPrefetch + $uncachedLanguages;
			}
		}

		if ( !empty( $entitiesToPrefetch ) ) {
			$languagesToPrefetch = array_keys( $languagesToPrefetch );
			$this->lookup->prefetchTerms( $entitiesToPrefetch, $termTypes, $languagesToPrefetch );
			$cache->setMultiple( $this->getPrefetchedTermsFromLookup(
				$entitiesToPrefetch,
				$languagesToPrefetch,
				$termTypes
			), $this->cacheEntryTTL );
		}
	}

	private function getUncachedLanguagesForEntityTerms(
		CacheInterface $cache,
		EntityId $id,
		array $termTypes,
		array $languages
	) {
		$uncachedLanguages = [];
		foreach ( $languages as $language ) {
			foreach ( $termTypes as $termType ) {
				$cacheKey = $this->getCacheKey( $id, $language, $termType );
				if ( $cacheKey === null ) {
					continue 2; // problem resolving the redirect / looking up the latest revision
				}

				// Multiple `has` calls are probably fine as long as we're using APC. If a more heavyweight cache is
				// used, a single `getMultiple` call is likely more performant.
				if ( !$cache->has( $cacheKey ) ) {
					$uncachedLanguages[$language] = true;
					continue 2; // no need to check other term types
				}
			}
		}

		return $uncachedLanguages;
	}

	private function getCacheKey( EntityId $id, string $language, string $termType ) {
		$resolutionResult = $this->redirectResolvingRevisionLookup->lookupLatestRevisionResolvingRedirect( $id );
		if ( $resolutionResult === null ) {
			return null;
		}

		return $this->buildCacheKey( $resolutionResult[1], $resolutionResult[0], $language, $termType );
	}

	private function getPrefetchedTermsFromLookup(
		array $entitiesToPrefetch,
		array $languagesToPrefetch,
		array $termTypes
	) {
		$terms = [];

		foreach ( $entitiesToPrefetch as $entity ) {
			foreach ( $languagesToPrefetch as $language ) {
				foreach ( $termTypes as $termType ) {
					$cacheKey = $this->getCacheKey( $entity, $language, $termType );
					if ( $cacheKey !== null ) {
						$terms[$cacheKey] = $this->getTermFromLookup( $entity, $termType, $language );
					}
				}
			}
		}

		return $terms;
	}

	private function getTermFromLookup( EntityId $entity, string $termType, string $language ) {
		if ( $termType === TermTypes::TYPE_LABEL || $termType === TermTypes::TYPE_DESCRIPTION ) {
			return $this->lookup->getPrefetchedTerm( $entity, $termType, $language );
		}

		return $this->lookup->getPrefetchedAliases( $entity, $language );
	}

}
