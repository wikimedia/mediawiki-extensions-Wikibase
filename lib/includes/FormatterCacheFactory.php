<?php

declare( strict_types = 1 );
namespace Wikibase\Lib;

use CachedBagOStuff;
use IBufferingStatsdDataFactory;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\FormatterCache\FormatterCacheServiceFactory;

/**
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
	 * @var FormatterCacheServiceFactory
	 */
	private $serviceFactory;

	/**
	 * @param int|string $formatterCacheType
	 * @param LoggerInterface $logger
	 * @param IBufferingStatsdDataFactory $statsdDataFactory
	 * @param string $cacheSecret
	 * @param FormatterCacheServiceFactory $serviceFactory
	 */
	public function __construct(
		$formatterCacheType,
		LoggerInterface $logger,
		IBufferingStatsdDataFactory $statsdDataFactory,
		string $cacheSecret,
		FormatterCacheServiceFactory $serviceFactory
	) {
		$this->formatterCacheType = $formatterCacheType;
		$this->logger = $logger;
		$this->statsdDataFactory = $statsdDataFactory;
		$this->cacheSecret = $cacheSecret;
		$this->serviceFactory = $serviceFactory;
	}

	public function getFormatterCache(): CacheInterface {
		$bagOStuff = $this->serviceFactory->newSharedCache( $this->formatterCacheType );
		if ( !$bagOStuff instanceof CachedBagOStuff ) {
			$bagOStuff = $this->serviceFactory->newInMemoryCache( $bagOStuff ); // wrap in an in-memory cache
		}

		$cache = $this->serviceFactory->newCache(
			$bagOStuff,
			'wikibase.repo.formatter.', // intentionally shared between repo and client
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
