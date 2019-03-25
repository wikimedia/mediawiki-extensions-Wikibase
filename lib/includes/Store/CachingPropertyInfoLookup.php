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
	protected $cacheKey;

	/**
	 * Maps properties to info arrays
	 *
	 * @var array[]|null
	 */
	protected $propertyInfo = null;

	/**
	 * @param PropertyInfoLookup $lookup The info lookup to call back to.
	 * @param WANObjectCache $cache
	 * @param int $cacheDuration         Number of seconds to keep the cached version for.
	 *                                   Defaults to 3600 seconds = 1 hour.
	 * @param string|null $cacheKey      The cache key to use, auto-generated per default.
	 *                                   Should be set to something including the wiki name
	 *                                   of the wiki that maintains the properties.
	 */
	public function __construct(
		PropertyInfoLookup $lookup,
		WANObjectCache $cache,
		$cacheDuration = 3600,
		$cacheKey = null
	) {
		$this->lookup = $lookup;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;

		if ( $cacheKey === null ) {
			// share cached data between wikis, only vary on language code.
			// XXX: should really include wiki ID of the wiki that maintains this!
			$cacheKey = __CLASS__;
		}

		$this->cacheKey = $cacheKey;
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
		// If we have a populated local class cache, use that, else fallback.
		// This could be an unnecessary optimization.
		if ( $this->hasClassBackedCache() && isset( $this->propertyInfo[$propertyId->getSerialization()] ) ) {
			$this->logger->debug(
				'{method}: using in class cached property info table', [ 'method' => __METHOD__ ]
			);
			return $this->propertyInfo[$propertyId->getSerialization()];
		}

		return $this->getPropertyInfoFromWANCache( $propertyId );
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 */
	public function getPropertyInfoFromWANCache( PropertyId $propertyId ) {
		$info = $this->cache->getWithSetCallback(
			$this->getSinglePropertyCacheKey( $propertyId ),
			$this->cacheDuration,
			function ( $oldValue, &$ttl, array &$setOpts ) use ( $propertyId ) {
				$allInfo = $this->getAllPropertyInfo();
				$propertyIdString = $propertyId->getSerialization();

				if ( isset( $allInfo[$propertyIdString] ) ) {
					return $allInfo[$propertyIdString];
				}

				// If there is no property, return false to not cache
				return false;
			}
		);

		if ( $info === false ) {
			return null;
		}

		return $info;
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
		if ( $this->hasClassBackedCache() ) {
			$usedCacheCallback = false;
			$this->propertyInfo = $this->cache->getWithSetCallback(
				$this->cacheKey,
				$this->cacheDuration,
				function ( $oldValue, &$ttl, array &$setOpts ) use ( &$usedCacheCallback ) {
					$this->logger->debug(
						'{method}: caching fresh property info table', [ 'method' => __METHOD__ ]
					);
					$usedCacheCallback = true;
					return $this->lookup->getAllPropertyInfo();
				}
			);

			if ( $usedCacheCallback ) {
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

	private function getSinglePropertyCacheKey( PropertyId $propertyId ) {
		return $this->cacheKey
			. self::SINGLE_PROPERTY_CACHE_KEY_SEPARATOR
			. $propertyId->getSerialization();
	}

}
