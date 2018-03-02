<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
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
	 * @param PropertyInfoLookup $lookup The info lookup to call back to.
	 * @param BagOStuff $cache           The cache to use for labels (typically from wfGetMainCache())
	 * @param int $cacheDuration         Number of seconds to keep the cached version for.
	 *                                   Defaults to 3600 seconds = 1 hour.
	 * @param string|null $cacheKey      The cache key to use, auto-generated per default.
	 *                                   Should be set to something including the wiki name
	 *                                   of the wiki that maintains the properties.
	 */
	public function __construct(
		PropertyInfoLookup $lookup,
		BagOStuff $cache,
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
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$propertyInfo = $this->getAllPropertyInfo();
		$id = $propertyId->getSerialization();

		if ( isset( $propertyInfo[$id] ) ) {
			return $propertyInfo[$id];
		}

		return null;
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
		if ( $this->propertyInfo === null ) {
			$this->propertyInfo = $this->cache->get( $this->cacheKey );

			if ( !is_array( $this->propertyInfo ) ) {
				$this->propertyInfo = $this->lookup->getAllPropertyInfo();
				$this->cache->set( $this->cacheKey, $this->propertyInfo, $this->cacheDuration );
				wfDebugLog( __CLASS__, __FUNCTION__ . ': cached fresh property info table' );
			} else {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': using cached property info table' );
			}
		}

		return $this->propertyInfo;
	}

}
