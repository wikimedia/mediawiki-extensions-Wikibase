<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Implementation of PropertyInfoStore wrapping the instance modifying the local
 * PropertyInfoStore and adjusting the property info cache accordingly.
 * Note: In-process cache (e.g. held by CachingPropertyInfoLookup instances)
 * is NOT updated when changes are done by the store.
 * Note: Cache keys used by this class should be in sync with keys used by
 * CachingPropertyInfoLookup instances.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CacheAwarePropertyInfoStore implements PropertyInfoStore {

	const SINGLE_PROPERTY_CACHE_KEY_SEPARATOR = ':';

	const CACHE_CLASS = 'CacheAwarePropertyInfoStore';

	/**
	 * @var PropertyInfoStore
	 */
	protected $innerStore;

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
	 * @param PropertyInfoStore $store The info store to call back to.
	 * @param WANObjectCache $cache
	 * @param int $cacheDuration       Number of seconds to keep the cached version for.
	 *                                 Defaults to 3600 seconds = 1 hour.
	 * @param string $cacheKeyGroup    Group name of the Wikibases to be used when generating global cache keys
	 */
	public function __construct(
		PropertyInfoStore $store,
		WANObjectCache $cache,
		$cacheDuration = 3600,
		$cacheKeyGroup = ''
	) {
		$this->innerStore = $store;
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
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 * @param array $info
	 *
	 * @throws InvalidArgumentException
	 */
	public function setPropertyInfo( PropertyId $propertyId, array $info ) {
		if ( !isset( $info[ PropertyInfoLookup::KEY_DATA_TYPE ] ) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoLookup::KEY_DATA_TYPE );
		}

		// update primary store
		$this->innerStore->setPropertyInfo( $propertyId, $info );

		$allPropertyInfo = $this->cache->get( $this->getFullTableCacheKey() );
		$id = $propertyId->getSerialization();

		// Update per property cache
		$this->logger->debug(
			'{method}: updating cache after updating property {id}',
			[
				'method' => __METHOD__,
				'id' => $id,
			]
		);

		// Deletes for all Data Centers
		$this->deleteCacheKeyForProperty( $propertyId );
		$this->deleteFullTableCacheKey();

		// Set for current Data Center only
		// Note: when set for current DC during a write a shorter ttl could be used to ensure a read away from writes recaches
		// the data in the not too distant future...
		$this->cache->set( $this->getSinglePropertyCacheKey( $propertyId ), $info, $this->cacheDuration );
		$this->cache->set( $this->getFullTableCacheKey(), $allPropertyInfo, $this->cacheDuration );
	}

	/**
	 * @see PropertyInfoStore::removePropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return bool
	 */
	public function removePropertyInfo( PropertyId $propertyId ) {
		$id = $propertyId->getSerialization();

		// update primary store
		$ok = $this->innerStore->removePropertyInfo( $propertyId );

		if ( !$ok ) {
			// nothing changed, nothing to do
			return false;
		}

		$allPropertyInfo = $this->cache->get( $this->getFullTableCacheKey() );

		unset( $allPropertyInfo[$id] );

		// Update external cache
		$this->logger->debug(
			'{method}: updating cache after removing property {id}',
			[
				'method' => __METHOD__,
				'id' => $id,
			]
		);

		// Delete for all Data Centers
		$this->deleteCacheKeyForProperty( $propertyId );
		$this->deleteFullTableCacheKey();

		// Set for current Data Center only
		// Note: when set for current DC during a write a shorter ttl could be used to ensure a read away from writes recaches
		// the data in the not too distant future...
		$this->cache->set( $this->getFullTableCacheKey(), $allPropertyInfo, $this->cacheDuration );

		return true;
	}

	private function getFullTableCacheKey() {
		return $this->cache->makeGlobalKey(
			self::CACHE_CLASS,
			$this->cacheKeyGroup
		);
	}

	private function getSinglePropertyCacheKey( PropertyId $propertyId ) {
		return $this->cache->makeGlobalKey(
			self::CACHE_CLASS,
			$this->cacheKeyGroup,
			$propertyId->getSerialization()
		);
	}

	private function deleteFullTableCacheKey() {
		$this->cache->delete( $this->getFullTableCacheKey() );
	}

	private function deleteCacheKeyForProperty( PropertyId $propertyId ) {
		$this->cache->delete( $this->getSinglePropertyCacheKey( $propertyId ) );
	}

}
