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
	protected $cacheKey;

	/**
	 * @param PropertyInfoStore $store The info store to call back to.
	 * @param WANObjectCache $cache
	 * @param int $cacheDuration       Number of seconds to keep the cached version for.
	 *                                 Defaults to 3600 seconds = 1 hour.
	 * @param string|null $cacheKey    The cache key to use, auto-generated per default.
	 *                                 Should be set to something including the wiki name
	 *                                 of the wiki that maintains the properties.
	 */
	public function __construct(
		PropertyInfoStore $store,
		WANObjectCache $cache,
		$cacheDuration = 3600,
		$cacheKey = null
	) {
		$this->innerStore = $store;
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

		$propertyInfo = $this->cache->get( $this->cacheKey );
		$id = $propertyId->getSerialization();

		$propertyInfo[$id] = $info;

		// Update per property cache
		$this->logger->debug(
			'{method}: updating cache after updating property {id}',
			[
				'method' => __METHOD__,
				'id' => $id,
			]
		);

		$this->deleteCacheKeyForProperty( $propertyId );
		$this->deleteFullTableCacheKey();
		$this->cache->set( $this->getSinglePropertyCacheKey( $propertyId ), $info, $this->cacheDuration );
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

		// Update external cache
		$this->logger->debug(
			'{method}: updating cache after removing property {id}',
			[
				'method' => __METHOD__,
				'id' => $id,
			]
		);

		$this->deleteCacheKeyForProperty( $propertyId );
		$this->deleteFullTableCacheKey();

		return true;
	}

	private function getSinglePropertyCacheKey( PropertyId $propertyId ) {
		return $this->cacheKey
			. self::SINGLE_PROPERTY_CACHE_KEY_SEPARATOR
			. $propertyId->getSerialization();
	}

	private function deleteFullTableCacheKey() {
		$this->cache->delete( $this->cacheKey );
	}

	private function deleteCacheKeyForProperty( PropertyId $propertyId ) {
		$this->cache->delete( $this->getSinglePropertyCacheKey( $propertyId ) );
	}

}
