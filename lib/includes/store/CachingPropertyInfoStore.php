<?php

namespace Wikibase;

use BagOStuff;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Class CachingPropertyInfoStore is an implementation of PropertyInfoStore
 * that maintains a cached copy of the property info in memcached.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class CachingPropertyInfoStore implements PropertyInfoStore {

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
	 * Maps properties to info arrays
	 *
	 * @var array[]|null
	 */
	protected $propertyInfo = null;

	/**
	 * @param PropertyInfoStore $store      The info store to call back to.
	 * @param BagOStuff  $cache            The cache to use for labels (typically from wfGetMainCache())
	 * @param int         $cacheDuration    Number of seconds to keep the cached version for.
	 *                                      Defaults to 3600 seconds = 1 hour.
	 * @param string|null $cacheKey         The cache key to use, auto-generated per default.
	 *                                      Should be set to something including the wiki name
	 *                                      of the wiki that maintains the properties.
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
	 * @see PropertyInfoStore::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$propertyInfo = $this->getAllPropertyInfo();
		$id = $propertyId->getNumericId();

		if ( isset( $propertyInfo[$id] ) ) {
			return $propertyInfo[$id];
		}

		return null;
	}

	/**
	 * @see PropertyInfoStore::getPropertyInfoForDataType
	 *
	 * @param string $dataType
	 *
	 * @return array[]
	 */
	public function getPropertyInfoForDataType( $dataType ) {
		$propertyInfo = $this->getAllPropertyInfo();
		$propertyInfoForDataType = array();

		foreach ( $propertyInfo as $id => $info ) {
			if ( $info[PropertyInfoStore::KEY_DATA_TYPE] === $dataType ) {
				$propertyInfoForDataType[$id] = $info;
			}
		}

		return $propertyInfoForDataType;
	}

	/**
	 * @see PropertyInfoStore::getAllPropertyInfo
	 *
	 * @return array[]
	 */
	public function getAllPropertyInfo() {
		if ( $this->propertyInfo === null ) {
			$this->propertyInfo = $this->cache->get( $this->cacheKey );

			if ( !is_array( $this->propertyInfo ) ) {
				$this->propertyInfo = $this->store->getAllPropertyInfo();
				$this->cache->set( $this->cacheKey, $this->propertyInfo, $this->cacheDuration );
				wfDebugLog( __CLASS__, __FUNCTION__ . ': cached fresh property info table' );
			} else {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': using cached property info table' );
			}
		}

		return $this->propertyInfo;
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
		if ( !isset( $info[ PropertyInfoStore::KEY_DATA_TYPE ] ) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoStore::KEY_DATA_TYPE );
		}

		// update primary store
		$this->store->setPropertyInfo( $propertyId, $info );

		// NOTE: Even if we don't have the propertyInfo locally, we still need to
		//       fully load it to update memcached.

		// Get local cached version.
		// NOTE: this may be stale at this point, if it was already loaded
		$propertyInfo = $this->getAllPropertyInfo();
		$id = $propertyId->getNumericId();

		// update local cache
		$propertyInfo[$id] = $info;
		$this->propertyInfo = $propertyInfo;

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
		$id = $propertyId->getNumericId();

		// if we don't know it, don't delete it.
		if ( is_array( $this->propertyInfo ) && !array_key_exists( $id, $this->propertyInfo ) ) {
			return false;
		}

		// update primary store
		$ok = $this->store->removePropertyInfo( $propertyId );

		if ( !$ok ) {
			// nothing changed, nothing to do
			return false;
		}

		// NOTE: Even if we don't have the propertyInfo locally, we still need to
		//       fully load it to update memcached.

		// Get local cached version.
		// NOTE: this may be stale at this point, if it was already loaded
		$propertyInfo = $this->getAllPropertyInfo();

		// update local cache
		unset( $propertyInfo[$id] );
		$this->propertyInfo = $propertyInfo;

		// update external cache
		wfDebugLog( __CLASS__, __FUNCTION__ . ': updating cache after removing property ' . $id );
		$this->cache->set( $this->cacheKey, $propertyInfo, $this->cacheDuration );

		return true;
	}

}
