<?php

declare( strict_types = 1 );
namespace Wikibase\Lib;

use ObjectCacheFactory;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikimedia\ObjectCache\CachedBagOStuff;
use Wikimedia\Stats\StatsFactory;

/**
 * Factory for accessing the shared cache
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheFactory {

	/**
	 * @var int|string
	 */
	private $termFallbackCacheType;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $cacheSecret;

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/**
	 * @var TermFallbackCacheServiceFactory
	 */
	private $serviceFactory;

	/**
	 * @var ObjectCacheFactory
	 */
	private $objectCacheFactory;

	/**
	 * @var int|null
	 */
	private $cacheVersion;

	/**
	 * @param int|string $cacheType
	 * @param LoggerInterface $logger
	 * @param StatsFactory $statsFactory
	 * @param string $cacheSecret
	 * @param TermFallbackCacheServiceFactory $serviceFactory
	 * @param int|null $cacheVersion
	 * @param ObjectCacheFactory $objectCacheFactory
	 */
	public function __construct(
		$cacheType,
		LoggerInterface $logger,
		StatsFactory $statsFactory,
		string $cacheSecret,
		TermFallbackCacheServiceFactory $serviceFactory,
		?int $cacheVersion,
		ObjectCacheFactory $objectCacheFactory
	) {
		$this->termFallbackCacheType = $cacheType;
		$this->logger = $logger;
		$this->statsFactory = $statsFactory;
		$this->cacheSecret = $cacheSecret;
		$this->serviceFactory = $serviceFactory;
		$this->cacheVersion = $cacheVersion;
		$this->objectCacheFactory = $objectCacheFactory;
	}

	public function getTermFallbackCache(): CacheInterface {
		$bagOStuff = $this->serviceFactory->newSharedCache( $this->termFallbackCacheType, $this->objectCacheFactory );
		if ( !$bagOStuff instanceof CachedBagOStuff ) {
			$bagOStuff = $this->serviceFactory->newInMemoryCache( $bagOStuff ); // wrap in an in-memory cache
		}

		$prefix = 'wikibase.repo.formatter.'; // intentionally shared between repo and client
		if ( $this->cacheVersion !== null ) {
			$prefix .= "$this->cacheVersion.";
		}

		$cache = $this->serviceFactory->newCache(
			$bagOStuff,
			$prefix,
			$this->cacheSecret
		);

		$cache->setLogger( $this->logger );

		return $this->serviceFactory->newStatslibRecordingCache(
			$cache,
			$this->statsFactory,
			[
				'miss' => 'wikibase.repo.formatterCache.miss',
				'hit' => 'wikibase.repo.formatterCache.hit',
			],
			'formatterCache_total'
		);
	}
}
