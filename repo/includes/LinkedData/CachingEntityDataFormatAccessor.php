<?php

namespace Wikibase\Repo\LinkedData;

use BagOStuff;
use LogicException;

/**
 * EntityDataFormatAccessor decorator that implements caching.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class CachingEntityDataFormatAccessor implements EntityDataFormatAccessor {

	/**
	 * @var EntityDataFormatAccessor
	 */
	private $entityDataFormatAccessor;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @var string
	 */
	private $cacheKeyPrefix;

	/**
	 * @param EntityDataFormatAccessor $entityDataFormatAccessor
	 * @param BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds. Defaults to 3600 (1 hour).
	 * @param string $cacheKeyPrefix Cache key prefix to use.
	 *     Important in case we're not in-process caching. Defaults to "wikibase"
	 */
	public function __construct(
		EntityDataFormatAccessor $entityDataFormatAccessor,
		BagOStuff $cache,
		$cacheDuration = 3600,
		$cacheKeyPrefix = 'wikibase'
	) {
		$this->entityDataFormatAccessor = $entityDataFormatAccessor;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	/**
	 * @see EntityDataFormatAccessor::getMimeTypes
	 */
	public function getMimeTypes( array $whitelist = null ) {
		return $this->load( 'mimeTypes', $whitelist );
	}

	/**
	 * @see EntityDataFormatAccessor::getFileExtensions
	 */
	public function getFileExtensions( array $whitelist = null ) {
		return $this->load( 'fileExtensions', $whitelist );
	}

	private function load( $type, array $whitelist = null ) {
		$fromCache = $this->loadFromCache( $type, $whitelist );

		if ( is_array( $fromCache ) ) {
			return $fromCache;
		}

		return $this->loadAndStore( $type, $whitelist );
	}

	/**
	 * @param string $type
	 * @param array|null $whitelist
	 *
	 * @return string
	 */
	private function getCacheKey( $type, array $whitelist = null ) {
		$whitelistHash = sha1( json_encode( $whitelist ) );

		return $this->cacheKeyPrefix . ':EntityDataFormats:' . $type . ':' . $whitelistHash;
	}

	/**
	 * @return array|bool false if not found in cache
	 */
	private function loadFromCache( $type, array $whitelist = null ) {
		$data = $this->cache->get( $this->getCacheKey( $type, $whitelist ) );

		if ( is_array( $data ) ) {
			return $data;
		}

		return false;
	}

	private function loadAndStore( $type, array $whitelist = null ) {
		if ( $type === 'fileExtensions' ) {
			$data = $this->entityDataFormatAccessor->getFileExtensions( $whitelist );
		} elseif( $type === 'mimeTypes' ) {
			$data = $this->entityDataFormatAccessor->getMimeTypes( $whitelist );
		} else {
			throw new LogicException( 'Invalid $type ' . $type );
		}

		$this->cache->set( $this->getCacheKey( $type, $whitelist ), $data, $this->cacheDuration );

		return $data;
	}

}
