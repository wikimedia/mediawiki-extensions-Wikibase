<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\TermFallbackCache;

use ObjectCacheFactory;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatslibRecordingSimpleCache;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\CachedBagOStuff;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheServiceFactory {

	/**
	 * Get a cached instance of the specified type of cache object.
	 *
	 * @param string|int $termFallbackCacheType A key in $wgObjectCaches.
	 * @param ObjectCacheFactory $objectCacheFactory
	 * @return BagOStuff
	 */
	public function newSharedCache( $termFallbackCacheType, ObjectCacheFactory $objectCacheFactory ): BagOStuff {
		return $objectCacheFactory->getInstance( $termFallbackCacheType );
	}

	public function newInMemoryCache( BagOStuff $bagOStuff ): CachedBagOStuff {
		return new CachedBagOStuff( $bagOStuff );
	}

	public function newCache( BagOStuff $bagOStuff, string $prefix, string $secret ): SimpleCacheWithBagOStuff {
		return new SimpleCacheWithBagOStuff( $bagOStuff, $prefix, $secret );
	}

	public function newStatslibRecordingCache(
		CacheInterface $inner,
		StatsFactory $statsFactory,
		array $statsdKeys,
		string $statsKey
	): StatslibRecordingSimpleCache {
		return new StatslibRecordingSimpleCache( $inner, $statsFactory, $statsdKeys, $statsKey );
	}
}
