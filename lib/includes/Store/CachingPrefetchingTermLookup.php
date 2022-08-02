<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;

/**
 * Prefetches terms from the Cache or via the provided PrefetchingTermLookup if not cached.
 *
 * Terms requested via the TermLookup methods are also buffered and cached.
 *
 * CacheInterface determines the medium of caching, and thus the availability (process, server, WAN).
 *
 * @license GPL-2.0-or-later
 */
final class CachingPrefetchingTermLookup implements PrefetchingTermLookup {

	use TermCacheKeyBuilder;

	private const DEFAULT_TTL = 60;
	private const RESOLVED_KEYS = 'resolvedKeys';
	private const UNRESOLVED_IDS = 'unresolvedIds';
	private const KEY_PARTS_MAP = 'keyPartsMap';
	private const UNCACHED_IDS = 'uncachedIds';
	private const UNCACHED_TERM_TYPES = 'uncachedTermTypes';
	private const UNCACHED_LANGUAGE_CODES = 'uncachedLanguageCodes';
	private const ENTITY_ID = 'entityId';
	private const TERM_TYPE = 'termType';
	private const LANGUAGE_CODE = 'languageCode';

	/**
	 * @var int
	 */
	private $cacheEntryTTL;

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @var array
	 */
	private $prefetchedTerms;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $lookup;

	/**
	 * @var RedirectResolvingLatestRevisionLookup
	 */
	private $redirectResolvingRevisionLookup;

	/**
	 * @var ContentLanguages
	 */
	private $termLanguages;

	public function __construct(
		CacheInterface $cache,
		PrefetchingTermLookup $lookup,
		RedirectResolvingLatestRevisionLookup $redirectResolvingRevisionLookup,
		ContentLanguages $termLanguages,
		?int $ttl = null
	) {
		$this->cache = $cache;
		$this->lookup = $lookup;
		$this->redirectResolvingRevisionLookup = $redirectResolvingRevisionLookup;
		$this->termLanguages = $termLanguages;
		$this->cacheEntryTTL = $ttl ?? self::DEFAULT_TTL;
	}

	public function prefetchTerms( array $entityIds, array $termTypes, array $languageCodes ): void {
		[
			self::UNCACHED_IDS => $uncachedIds,
			self::UNCACHED_TERM_TYPES => $uncachedTermTypes,
			self::UNCACHED_LANGUAGE_CODES => $uncachedLanguageCodes,
		] = $this->prefetchCachedTerms( $entityIds, $termTypes, $this->filterValidTermLanguages( $languageCodes ) );

		$this->prefetchAndCache( $uncachedIds, $uncachedTermTypes, $uncachedLanguageCodes );
	}

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		if ( isset( $this->prefetchedTerms[$entityId->getSerialization()][$termType][$languageCode] ) ) {
			return $this->prefetchedTerms[$entityId->getSerialization()][$termType][$languageCode];
		}
		return $this->lookup->getPrefetchedTerm( $entityId, $termType, $languageCode );
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		if ( isset( $this->prefetchedTerms[$entityId->getSerialization()][TermTypes::TYPE_ALIAS][$languageCode] ) ) {
			// @phan-suppress-next-line PhanTypeArraySuspiciousNullable
			return $this->prefetchedTerms[$entityId->getSerialization()][TermTypes::TYPE_ALIAS][$languageCode];
		}
		return $this->lookup->getPrefetchedAliases( $entityId, $languageCode );
	}

	public function getLabel( EntityId $entityId, $languageCode ): ?string {
		if ( !$this->termLanguages->hasLanguage( $languageCode ) ) {
			return null;
		}

		return $this->getTerm( $entityId, $languageCode, TermTypes::TYPE_LABEL );
	}

	public function getDescription( EntityId $entityId, $languageCode ): ?string {
		if ( !$this->termLanguages->hasLanguage( $languageCode ) ) {
			return null;
		}

		return $this->getTerm( $entityId, $languageCode, TermTypes::TYPE_DESCRIPTION );
	}

	private function getTerm( EntityId $entityId, string $languageCode, string $termType ) {
		$cachedTerm = $this->getBufferedOrCachedEntry( $entityId, $termType, $languageCode );
		if ( $cachedTerm !== null ) {
			return $cachedTerm ?: null;
		}

		if ( $termType === TermTypes::TYPE_LABEL ) {
			$freshTerm = $this->lookup->getLabel( $entityId, $languageCode );
		} else {
			$freshTerm = $this->lookup->getDescription( $entityId, $languageCode );
		}

		if ( $freshTerm !== null ) {
			$this->bufferAndCacheExistingTerm( $entityId, $termType, $languageCode, $freshTerm );
		} else {
			$this->bufferAndCacheMissingTerm( $entityId, $termType, $languageCode );
		}

		return $freshTerm;
	}

	public function getLabels( EntityId $entityId, array $languageCodes ) {
		$validTermLanguageCodes = $this->filterValidTermLanguages( $languageCodes );
		return $this->getMultipleTermsByLanguage( $entityId, TermTypes::TYPE_LABEL, $validTermLanguageCodes );
	}

	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		$validTermLanguageCodes = $this->filterValidTermLanguages( $languageCodes );
		return $this->getMultipleTermsByLanguage( $entityId, TermTypes::TYPE_DESCRIPTION, $validTermLanguageCodes );
	}

	private function prefetchCachedTerms( array $entityIds, array $termTypes, array $languageCodes ): array {
		[
			self::RESOLVED_KEYS => $cacheKeys,
			self::UNRESOLVED_IDS => $unresolvedIds, // This is intentionally unused.
			self::KEY_PARTS_MAP => $keyPartsMap,
		] = $this->getCacheKeys( $entityIds, $termTypes, $languageCodes );
		$cacheResults = $this->cache->getMultiple( $cacheKeys );
		$uncachedIds = [];
		$uncachedTermTypes = [];
		$uncachedLanguageCodes = [];
		foreach ( $cacheResults as $cacheKey => $cacheResult ) {
			$entityId = $keyPartsMap[$cacheKey][self::ENTITY_ID];
			$termType = $keyPartsMap[$cacheKey][self::TERM_TYPE];
			$languageCode = $keyPartsMap[$cacheKey][self::LANGUAGE_CODE];
			if ( $cacheResult === null ) {
				$uncachedIds[$entityId->getSerialization()] = $entityId;
				$uncachedTermTypes[$termType] = $termType;
				$uncachedLanguageCodes[$languageCode] = $languageCode;
				continue;
			}

			// we have data from the cache
			$this->setPrefetchedTermBuffer( $entityId, $termType, $languageCode, $cacheResult );
		}

		return [
			self::UNCACHED_IDS => array_values( $uncachedIds ),
			self::UNCACHED_TERM_TYPES => array_values( $uncachedTermTypes ),
			self::UNCACHED_LANGUAGE_CODES => array_values( $uncachedLanguageCodes ),
		];
	}

	private function getCacheKeys( array $entityIds, array $termTypes, array $languageCodes ): array {
		$cacheKeys = [];
		$unresolvedIds = [];
		$keyPartsMap = [];
		foreach ( $entityIds as $entityId ) {
			foreach ( $termTypes as $termType ) {
				foreach ( $languageCodes as $languageCode ) {
					$key = $this->getCacheKey( $entityId, $languageCode, $termType );
					if ( $key === null ) {
						$unresolvedIds[] = $key;
						continue 2; // problem resolving the redirect / looking up the latest revision
					}
					$cacheKeys[] = $key;
					$keyPartsMap[$key] = [
						self::ENTITY_ID => $entityId,
						self::TERM_TYPE => $termType,
						self::LANGUAGE_CODE => $languageCode,
					];
				}
			}
		}
		return [
			self::RESOLVED_KEYS => $cacheKeys,
			self::UNRESOLVED_IDS => $unresolvedIds,
			self::KEY_PARTS_MAP => $keyPartsMap,
		];
	}

	private function prefetchAndCache( array $uncachedIds, array $uncachedTermTypes, array $uncachedLanguageCodes ): void {
		$this->lookup->prefetchTerms( $uncachedIds, $uncachedTermTypes, $uncachedLanguageCodes );
		$this->cache->setMultiple( $this->getPrefetchedTermsFromLookup(
			$uncachedIds,
			$uncachedLanguageCodes,
			$uncachedTermTypes
		), $this->cacheEntryTTL );
	}

	private function getPrefetchedTermsFromLookup(
		array $entitiesToPrefetch,
		array $languagesToPrefetch,
		array $termTypes
	): array {
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

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 * @param string|string[]|false $value string for existing label or description, string[] for existing aliases, false for term known
	 *                                     to not exist.
	 */
	private function setPrefetchedTermBuffer( EntityId $entityId, string $termType, string $languageCode, $value ): void {
		if ( !isset( $this->prefetchedTerms[$entityId->getSerialization()] ) ) {
			$this->prefetchedTerms[$entityId->getSerialization()] = [];
		}
		if ( !isset( $this->prefetchedTerms[$entityId->getSerialization()][$termType] ) ) {
			$this->prefetchedTerms[$entityId->getSerialization()][$termType] = [];
		}

		$this->prefetchedTerms[$entityId->getSerialization()][$termType][$languageCode] = $value;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 * @param string|string[] $freshTerm string for existing label or description, string[] for existing aliases
	 *                                   Should never be null or false @see bufferAndCacheMissingTerm
	 */
	private function bufferAndCacheExistingTerm( EntityId $entityId, string $termType, string $languageCode, $freshTerm ): void {
		$this->setPrefetchedTermBuffer( $entityId, $termType, $languageCode, $freshTerm );
		$this->cache->set(
			$this->getCacheKey( $entityId, $languageCode, $termType ),
			$freshTerm,
			$this->cacheEntryTTL
		);
	}

	private function bufferAndCacheMissingTerm( EntityId $entityId, string $termType, string $languageCode ): void {
		$this->setPrefetchedTermBuffer( $entityId, $termType, $languageCode, false );
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termType );
		if ( $cacheKey === null ) {
			return;
		}
		$this->cache->set(
			$cacheKey,
			false,
			$this->cacheEntryTTL
		);
	}

	private function getCacheKey( EntityId $id, string $language, string $termType ) {
		$resolutionResult = $this->redirectResolvingRevisionLookup->lookupLatestRevisionResolvingRedirect( $id );
		if ( $resolutionResult === null ) {
			return null;
		}

		return $this->buildCacheKey( $resolutionResult[1], $resolutionResult[0], $language, $termType );
	}

	private function getBufferedOrCachedEntry( EntityId $entityId, string $termType, string $languageCode ) {
		// Check if it's prefetched already.
		$prefetchedTerm = $this->getPrefetchedTerm( $entityId, $termType, $languageCode );
		if ( $prefetchedTerm !== null ) {
			return $prefetchedTerm;
		}

		// Try getting it from cache
		$cacheKey = $this->getCacheKey( $entityId, $languageCode, $termType );
		return $cacheKey === null ? null : $this->cache->get( $cacheKey );
	}

	private function getMultipleTermsByLanguageFromBuffer( EntityId $entityId, string $termType, array $languages ) {
		$terms = [];

		// Lookup in termbuffer
		foreach ( $languages as $language ) {
			$prefetchedTerm = $this->getPrefetchedTerm( $entityId, $termType, $language );
			if ( $prefetchedTerm !== null ) {
				$terms[$language] = $prefetchedTerm;
			}
		}

		return $terms;
	}

	private function getMultipleTermsByLanguageFromCache( EntityId $entityId, string $termType, array $languages ) {
		$terms = [];

		$languagesToCacheKeys = [];

		foreach ( $languages as $language ) {
			$cacheKey = $this->getCacheKey( $entityId, $language, $termType );
			if ( $cacheKey ) {
				$languagesToCacheKeys[$language] = $cacheKey;
			}
		}

		$cacheKeysToLanguages = array_flip( $languagesToCacheKeys );
		$cacheTerms = $this->cache->getMultiple( array_values( $languagesToCacheKeys ) );

		foreach ( $cacheTerms as $key => $term ) {
			if ( $term !== null ) {
				$terms[$cacheKeysToLanguages[$key]] = $term;
				$this->setPrefetchedTermBuffer( $entityId, $termType, $cacheKeysToLanguages[$key], $term );
			}
		}

		return $terms;
	}

	private function getMultipleTermsByLanguageFromLookup( EntityId $entityId, string $termType, array $languages ) {
		if ( $termType === TermTypes::TYPE_LABEL ) {
			$freshTerms = $this->lookup->getLabels( $entityId, $languages );
		} else {
			$freshTerms = $this->lookup->getDescriptions( $entityId, $languages );
		}

		foreach ( $languages as $language ) {
			if ( !isset( $freshTerms[$language] ) || !$freshTerms[$language] ) {
				$this->bufferAndCacheMissingTerm( $entityId, $termType, $language );
			} else {
				$this->bufferAndCacheExistingTerm( $entityId, $termType, $language, $freshTerms[$language] );
			}
		}

		return $freshTerms;
	}

	private function getMultipleTermsByLanguage( EntityId $entityId, string $termType, array $languages ) {
		$terms = $this->getMultipleTermsByLanguageFromBuffer( $entityId, $termType, $languages );

		// languages without prefetched terms
		$unbufferedLanguages = array_diff( $languages, array_keys( $terms ) );
		if ( empty( $unbufferedLanguages ) ) {
			return array_filter( $terms );
		}

		$terms = array_merge(
			$terms,
			$this->getMultipleTermsByLanguageFromCache( $entityId, $termType, $unbufferedLanguages )
		);

		$unCachedAndUnbufferedLanguages = array_values(
			array_diff( $languages, array_keys( $terms ) )
		);
		if ( empty( $unCachedAndUnbufferedLanguages ) ) {
			return array_filter( $terms );
		}

		$terms = array_merge(
			$terms,
			$this->getMultipleTermsByLanguageFromLookup( $entityId, $termType, $unCachedAndUnbufferedLanguages )
		);

		return array_filter( $terms );
	}

	private function filterValidTermLanguages( array $languageCodes ): array {
		return array_filter( $languageCodes, [ $this->termLanguages, 'hasLanguage' ] );
	}

}
