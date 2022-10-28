<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikibase\DataModel\Entity\NumericPropertyId;

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

	public const CACHE_CLASS = 'CacheAwarePropertyInfoStore';

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
	 * @param NumericPropertyId $propertyId
	 * @param array $info
	 *
	 * @throws InvalidArgumentException
	 */
	public function setPropertyInfo( NumericPropertyId $propertyId, array $info ) {
		if ( !isset( $info[ PropertyInfoLookup::KEY_DATA_TYPE ] ) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoLookup::KEY_DATA_TYPE );
		}

		// update primary store
		$this->innerStore->setPropertyInfo( $propertyId, $info );

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

		// Trying to set the same key would be ignored if it is within the
		// tombstone TTL, so don't do that.
	}

	/**
	 * @see PropertyInfoStore::removePropertyInfo
	 *
	 * @param NumericPropertyId $propertyId
	 *
	 * @return bool
	 */
	public function removePropertyInfo( NumericPropertyId $propertyId ) {
		$id = $propertyId->getSerialization();

		// update primary store
		$ok = $this->innerStore->removePropertyInfo( $propertyId );

		if ( !$ok ) {
			// nothing changed, nothing to do
			return false;
		}

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

		// Trying to set the same key would be ignored if it is within the
		// tombstone TTL, so don't do that.
		return true;
	}

	private function getFullTableCacheKey() {
		return $this->cache->makeGlobalKey(
			self::CACHE_CLASS,
			$this->cacheKeyGroup
		);
	}

	private function getSinglePropertyCacheKey( NumericPropertyId $propertyId ) {
		return $this->cache->makeGlobalKey(
			self::CACHE_CLASS,
			$this->cacheKeyGroup,
			$propertyId->getSerialization()
		);
	}

	private function deleteFullTableCacheKey() {
		$this->cache->delete( $this->getFullTableCacheKey() );
	}

	private function deleteCacheKeyForProperty( NumericPropertyId $propertyId ) {
		$this->cache->delete( $this->getSinglePropertyCacheKey( $propertyId ) );
	}

}
