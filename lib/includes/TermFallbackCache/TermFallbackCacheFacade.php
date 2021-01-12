<?php

declare( strict_types=1 );
namespace Wikibase\Lib\TermFallbackCache;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\TermCacheKeyBuilder;

/**
 * TermFallbackCacheFacade is class to allow for simplified
 * interaction with the shared cache used for storing TermFallback objects
 * (also known as the term fallback cache or formatter cache).
 *
 * The cache returns TermFallbackCacheFacade::NO_VALUE in the
 * case there is no entry in the cache.
 *
 * Storing null values is also allowed as this indicates we
 * have already checked the database for the term but found nothing.
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheFacade {

	use TermFallbackSerializerTrait;
	use TermCacheKeyBuilder;

	public const NO_VALUE = false;

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $cacheTtlInSeconds;

	public function __construct(
		CacheInterface $cache,
		int $cacheTtlInSeconds
	) {
		$this->cache = $cache;
		$this->cacheTtlInSeconds = $cacheTtlInSeconds;
	}

	/**
	 * @param EntityId $targetEntityId
	 * @param int $revisionId
	 * @param string $languageCode
	 * @param string $termType
	 * @return false|TermFallback|null
	 *  - returning null indicates we have already checked the database and stored it's null response in the cache.
	 *  - returning false indicates there is no cache entry.
	 */
	public function get( EntityId $targetEntityId, int $revisionId, string $languageCode, string $termType ) {
		$cacheKey = $this->buildCacheKey( $targetEntityId, $revisionId, $languageCode, $termType );
		$termFallback = $this->cache->get( $cacheKey, self::NO_VALUE );

		if ( $termFallback === self::NO_VALUE ) {
			return self::NO_VALUE;
		}

		return $this->unserialize( $termFallback );
	}

	/**
	 * @param null|TermFallback $termFallback
	 * @param EntityId $targetEntityId
	 * @param int $revisionId
	 * @param string $languageCode
	 * @param string $termType
	 */
	public function set(
		?TermFallback $termFallback,
		EntityId $targetEntityId,
		int $revisionId,
		string $languageCode,
		string $termType
	): void {
		$cacheKey = $this->buildCacheKey( $targetEntityId, $revisionId, $languageCode, $termType );
		$serialization = $this->serialize( $termFallback );

		$this->cache->set( $cacheKey, $serialization, $this->cacheTtlInSeconds );
	}
}
