<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\TermFallbackCache;

use BagOStuff;
use CachedBagOStuff;
use IBufferingStatsdDataFactory;
use ObjectCacheFactory;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;

/**
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheServiceFactory {

	public function newSharedCache( $termFallbackCacheType, ObjectCacheFactory $objectCacheFactory ): BagOStuff {
		return $objectCacheFactory->getInstance( $termFallbackCacheType );
	}

	public function newInMemoryCache( BagOStuff $bagOStuff ): CachedBagOStuff {
		return new CachedBagOStuff( $bagOStuff );
	}

	public function newCache( BagOStuff $bagOStuff, string $prefix, string $secret ): SimpleCacheWithBagOStuff {
		return new SimpleCacheWithBagOStuff( $bagOStuff, $prefix, $secret );
	}

	public function newStatsdRecordingCache(
		CacheInterface $inner,
		IBufferingStatsdDataFactory $statsdDataFactory,
		array $statsKeys
	): StatsdRecordingSimpleCache {
		return new StatsdRecordingSimpleCache( $inner, $statsdDataFactory, $statsKeys );
	}
}
