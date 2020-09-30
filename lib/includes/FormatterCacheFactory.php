<?php

declare( strict_types = 1 );
namespace Wikibase\Lib;

use CachedBagOStuff;
use IBufferingStatsdDataFactory;
use ObjectCache;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 *
 * Factory for accessing the shared cache
 *
 * @license GPL-2.0-or-later
 */
class FormatterCacheFactory {

	/**
	 * @var int|string
	 */
	private $formatterCacheType;

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
	 * @param int|string $formatterCacheType
	 * @param LoggerInterface $logger
	 * @param IBufferingStatsdDataFactory $statsdDataFactory
	 * @param string $cacheSecret
	 */
	public function __construct(
		$formatterCacheType,
		LoggerInterface $logger,
		IBufferingStatsdDataFactory $statsdDataFactory,
		string $cacheSecret

	) {
		$this->formatterCacheType = $formatterCacheType;
		$this->logger = $logger;
		$this->cacheSecret = $cacheSecret;
		$this->statsdDataFactory = $statsdDataFactory;
	}

	/**
	 * @return CacheInterface
	 */
	public function getFormatterCache(): CacheInterface {

		// Get the default shared cache wrapped in an in memory cache
		$bagOStuff = ObjectCache::getInstance( $this->formatterCacheType );
		if ( !$bagOStuff instanceof CachedBagOStuff ) {
			$bagOStuff = new CachedBagOStuff( $bagOStuff );
		}

		$cache = new SimpleCacheWithBagOStuff(
			$bagOStuff,
			'wikibase.repo.formatter.',
			$this->cacheSecret
		);

		$cache->setLogger( $this->logger );

		$cache = new StatsdRecordingSimpleCache(
			$cache,
			$this->statsdDataFactory,
			[
				"miss" => 'wikibase.repo.formatterCache.miss',
				"hit" => 'wikibase.repo.formatterCache.hit'
			]
		);

		return $cache;
	}
}
