<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Implementation of PropertyInfoStore wrapping the instance modifying the local
 * PropertyInfoStore and adjusting the property info cache accordingly.
 * Note: In-process cache (e.g. held by CachingPropertyInfoLookup instances)
 * is NOT updated when changes are done by the store.
 * Note: Cache keys used by this class should be in sync with keys used by
 * CachingPropertyInfoLookup instances.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class CacheAwarePropertyInfoStore implements PropertyInfoStore {

	/**
	 * @var PropertyInfoStore
	 */
	protected $store;

	/**
	 * @var BagOStuff
	 */
	protected $cache;

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
	 * @param BagOStuff $cache         The cache to use for labels (typically from wfGetMainCache())
	 * @param int $cacheDuration       Number of seconds to keep the cached version for.
	 *                                 Defaults to 3600 seconds = 1 hour.
	 * @param string|null $cacheKey    The cache key to use, auto-generated per default.
	 *                                 Should be set to something including the wiki name
	 *                                 of the wiki that maintains the properties.
	 */
	public function __construct(
		PropertyInfoStore $store,
		BagOStuff $cache,
		$cacheDuration = 3600,
		$cacheKey = null
	) {
		$this->store = $store;
		$this->cache = $cache;
		$this->cacheDuration = $cacheDuration;

		if ( $cacheKey === null ) {
			// share cached data between wikis, only vary on language code.
			// XXX: should really include wiki ID of the wiki that maintains this!
			$cacheKey = __CLASS__;
		}

		$this->cacheKey = $cacheKey;
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
		$this->store->setPropertyInfo( $propertyId, $info );

		$propertyInfo = $this->cache->get( $this->cacheKey );
		$id = $propertyId->getSerialization();

		$propertyInfo[$id] = $info;

		// update external cache
		wfDebugLog( __CLASS__, __FUNCTION__ . ': updating cache after updating property ' . $id );
		$this->cache->set( $this->cacheKey, $propertyInfo, $this->cacheDuration );
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
		$ok = $this->store->removePropertyInfo( $propertyId );

		if ( !$ok ) {
			// nothing changed, nothing to do
			return false;
		}

		$propertyInfo = $this->cache->get( $this->cacheKey );

		unset( $propertyInfo[$id] );

		// update external cache
		wfDebugLog( __CLASS__, __FUNCTION__ . ': updating cache after removing property ' . $id );
		$this->cache->set( $this->cacheKey, $propertyInfo, $this->cacheDuration );

		return true;
	}

}
