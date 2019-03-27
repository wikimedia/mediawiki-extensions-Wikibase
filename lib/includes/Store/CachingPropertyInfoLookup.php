<?php

namespace Wikibase\Lib\Store;

use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Class CachingPropertyInfoLookup is an implementation of PropertyInfoLookup
 * that maintains a cached copy of the property info.
 * Note: Cache keys used by this class should be in sync with keys used by
 * CacheAwarePropertyInfoStore instance.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CachingPropertyInfoLookup implements PropertyInfoLookup {

	const SINGLE_PROPERTY_CACHE_KEY_SEPARATOR = ':';

	/**
	 * @var PropertyInfoLookup
	 */
	protected $lookup;

	/**
	 * @var WANObjectCache
	 */
	protected $cache;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var int
	 */
	protected $cacheDuration;

	/**
	 * @var string
	 */
	protected $cacheKeyGroup;

	/**
	 * Maps properties to info arrays
	 *
	 * @var array[]|null
	 */
	protected $propertyInfo = null;

	/**
	 * @param PropertyInfoLookup $lookup The info lookup to call back to.
	 * @param WANObjectCache $cache
	 * @param string $cacheKeyGroup Group name of the Wikibases to be used when generating global cache keys
	 * @param int $cacheDuration Number of seconds to keep the cached version for.
	 *                                   Defaults to 3600 seconds = 1 hour.
	 */
	public function __construct(
		PropertyInfoLookup $lookup,
		WANObjectCache $cache,
		$cacheKeyGroup,
		$cacheDuration = 3600
	) {
		$this->lookup = $lookup;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;

		if ( $cacheKeyGroup === '' ) {
			throw new \InvalidArgumentException( '$cacheKeyGroup should be specified' );
		}

		$this->cacheKeyGroup = $cacheKeyGroup;
		// TODO: Inject
		$this->logger = LoggerFactory::getInstance( 'Wikibase' );
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$id = $propertyId->getSerialization();

		// If we have a populated local class cache, use that, else fallback.
		// If we call getPropertyInfoFromWANCache already, the getWithSetCallback there will use the class
		// cache anyway when it calls $this->getAllPropertyInfo
		if ( $this->hasClassBackedCache() && isset( $this->propertyInfo[$id] ) ) {
			$this->logger->debug(
				'{method}: using in class cached property info table', [ 'method' => __METHOD__ ]
			);
			return $this->propertyInfo[$id];
		}

		return $this->getPropertyInfoFromWANCache( $propertyId );
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 */
	private function getPropertyInfoFromWANCache( PropertyId $propertyId ) {
		$info = $this->cache->getWithSetCallback(
			$this->getSinglePropertyCacheKey( $propertyId ),
			$this->cacheDuration,
			function ( $oldValue, &$ttl, array &$setOpts ) use ( $propertyId ) {
				$allInfo = $this->getAllPropertyInfo();
				$id = $propertyId->getSerialization();

				// If there is no property, return false to not cache
				return $allInfo[$id] ?? false;
			}
		);

		return $info ?: null;
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfoForDataType
	 *
	 * @param string $dataType
	 *
	 * @return array[] Array containing serialized property IDs as keys and info arrays as values
	 */
	public function getPropertyInfoForDataType( $dataType ) {
		$propertyInfo = $this->getAllPropertyInfo();
		$propertyInfoForDataType = [];

		foreach ( $propertyInfo as $id => $info ) {
			if ( $info[PropertyInfoLookup::KEY_DATA_TYPE] === $dataType ) {
				$propertyInfoForDataType[$id] = $info;
			}
		}

		return $propertyInfoForDataType;
	}

	/**
	 * @see PropertyInfoLookup::getAllPropertyInfo
	 *
	 * @return array[] Array containing serialized property IDs as keys and info arrays as values
	 */
	public function getAllPropertyInfo() {
		if ( !$this->hasClassBackedCache() ) {
			$cacheHit = true;
			$this->propertyInfo = $this->cache->getWithSetCallback(
				$this->getFullTableCacheKey(),
				$this->cacheDuration,
				function ( $oldValue, &$ttl, array &$setOpts ) use ( &$cacheHit ) {
					$this->logger->debug(
						'{method}: caching fresh property info table', [ 'method' => __METHOD__ ]
					);
					$cacheHit = false;
					return $this->lookup->getAllPropertyInfo();
				}
			);

			if ( $cacheHit ) {
				$this->logger->debug(
					'{method}: using cached property info table', [ 'method' => __METHOD__ ]
				);
			}
		}

		return $this->propertyInfo;
	}

	private function hasClassBackedCache() {
		return $this->propertyInfo !== null;
	}

	private function getFullTableCacheKey() {
		return $this->cache->makeGlobalKey(
			CacheAwarePropertyInfoStore::CACHE_CLASS,
			$this->cacheKeyGroup
		);
	}

	private function getSinglePropertyCacheKey( PropertyId $propertyId ) {
		return $this->cache->makeGlobalKey(
			CacheAwarePropertyInfoStore::CACHE_CLASS,
			$this->cacheKeyGroup,
			$propertyId->getSerialization()
		);
	}

}
