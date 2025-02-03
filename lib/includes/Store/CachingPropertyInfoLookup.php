<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
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

	/**
	 * @var PropertyInfoLookup
	 */
	protected $lookup;

	/**
	 * @var BagOStuff|WANObjectCache
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
	 * @var array[]
	 */
	protected $propertyInfo = [];

	/**
	 * @var bool Is the propertyInfo fully populated?
	 */
	protected $propertyInfoFullyPopulated = false;

	/**
	 * @param PropertyInfoLookup $lookup The info lookup to call back to.
	 * @param BagOStuff|WANObjectCache $cache
	 * @param string $cacheKeyGroup Group name of the Wikibases to be used when generating global cache keys
	 * @param int $cacheDuration Number of seconds to keep the cached version for.
	 *                                   Defaults to 3600 seconds = 1 hour.
	 */
	public function __construct(
		PropertyInfoLookup $lookup,
		$cache,
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

		if ( isset( $this->propertyInfo[$id] ) ) {
			$this->logger->debug(
				'{method}: using in class cached property info table', [ 'method' => __METHOD__ ]
			);
			return $this->propertyInfo[$id];
		}

		if ( $this->propertyInfoFullyPopulated ) {
			$this->logger->debug(
				'{method}: using in class cached property info table', [ 'method' => __METHOD__ ]
			);
			return null;
		}

		$info = $this->getPropertyInfoFromCache( $propertyId );
		$this->propertyInfo[$id] = $info;
		return $info;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 */
	private function getPropertyInfoFromCache( PropertyId $propertyId ) {
		$info = $this->cache->getWithSetCallback(
			$this->getSinglePropertyCacheKey( $propertyId ),
			$this->cacheDuration,
			function () use ( $propertyId ) {
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
		if ( !$this->propertyInfoFullyPopulated ) {
			$wanCacheHit = true;
			$fname = __METHOD__;
			$this->propertyInfo = $this->cache->getWithSetCallback(
				$this->getFullTableCacheKey(),
				$this->cacheDuration,
				function () use ( &$wanCacheHit, $fname ) {
					$this->logger->debug(
						'{method}: caching fresh property info table', [ 'method' => $fname ]
					);
					$wanCacheHit = false;
					return $this->lookup->getAllPropertyInfo();
				}
			);
			if ( !$wanCacheHit ) {
				$this->logger->debug(
					'{method}: Repopulating property info table in WAN cache', [ 'method' => __METHOD__ ]
				);
			}
			$this->propertyInfoFullyPopulated = true;
		}

		return $this->propertyInfo;
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
