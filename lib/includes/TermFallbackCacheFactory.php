<?php

declare( strict_types = 1 );
namespace Wikibase\Lib;

use CachedBagOStuff;
use IBufferingStatsdDataFactory;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;

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
	 * @var IBufferingStatsdDataFactory
	 */
	private $statsdDataFactory;

	/**
	 * @var TermFallbackCacheServiceFactory
	 */
	private $serviceFactory;

	/**
	 * @var int|null
	 */
	private $cacheVersion;

	/**
	 * @param int|string $cacheType
	 * @param LoggerInterface $logger
	 * @param IBufferingStatsdDataFactory $statsdDataFactory
	 * @param string $cacheSecret
	 * @param TermFallbackCacheServiceFactory $serviceFactory
	 * @param int|null $cacheVersion
	 */
	public function __construct(
		$cacheType,
		LoggerInterface $logger,
		IBufferingStatsdDataFactory $statsdDataFactory,
		string $cacheSecret,
		TermFallbackCacheServiceFactory $serviceFactory,
		?int $cacheVersion
	) {
		$this->termFallbackCacheType = $cacheType;
		$this->logger = $logger;
		$this->statsdDataFactory = $statsdDataFactory;
		$this->cacheSecret = $cacheSecret;
		$this->serviceFactory = $serviceFactory;
		$this->cacheVersion = $cacheVersion;
	}

	public function getTermFallbackCache(): CacheInterface {
		$bagOStuff = $this->serviceFactory->newSharedCache( $this->termFallbackCacheType );
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

		return $this->serviceFactory->newStatsdRecordingCache(
			$cache,
			$this->statsdDataFactory,
			[
				'miss' => 'wikibase.repo.formatterCache.miss',
				'hit' => 'wikibase.repo.formatterCache.hit',
			]
		);
	}
}
