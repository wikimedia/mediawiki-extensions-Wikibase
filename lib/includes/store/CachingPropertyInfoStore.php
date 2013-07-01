<?php
 /**
 *
 * Copyright © 26.06.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GPL 2+
 * @file
 * @ingroup WikibaseLib
 *
 * @author Daniel Kinzler
 */


namespace Wikibase;

/**
 * Class CachingPropertyInfoStore is an implementation of PropertyInfoStore
 * that maintains a cached copy of the property info in memcached.
 *
 * @since 0.4
 *
 * @package Wikibase
 */
class CachingPropertyInfoStore implements PropertyInfoStore {

	/**
	 * @var PropertyInfoStore
	 */
	protected $store;

	/**
	 * @var \BagOStuff
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
	 * @var array[]
	 */
	protected $propertyInfo = null;

	/**
	 * @param PropertyInfoStore $store      The info store to call back to.
	 * @param \BagOStuff  $cache            The cache to use for labels (typically from wfGetMainCache())
	 * @param int         $cacheDuration    Number of seconds to keep the cached version for.
	 *                                      Defaults to 3600 seconds = 1 hour.
	 * @param string|null $cacheKey         The cache key to use, auto-generated per default.
	 *                                      Should be set to something including the wiki name
	 *                                      of the wiki that maintains the properties.
	 */
	public function __construct(
		PropertyInfoStore $store,
		\BagOStuff $cache,
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
	 * @see   PropertyInfoStore::getPropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @return array|null
	 * @throws \InvalidArgumentException
	 */
	public function getPropertyInfo( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		$propertyInfo = $this->getAllPropertyInfo();
		$id = $propertyId->getNumericId();

		if ( isset( $propertyInfo[$id] ) ) {
			return $propertyInfo[$id];
		}

		return null;
	}

	/**
	 * @see   PropertyInfoStore::getAllPropertyInfo
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

		assert( is_array( $this->propertyInfo ) );
		return $this->propertyInfo;
	}

	/**
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param EntityId $propertyId
	 * @param array    $info
	 */
	public function setPropertyInfo( EntityId $propertyId, array $info ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		if ( !isset( $info[ PropertyInfoStore::KEY_DATA_TYPE ]) ) {
			throw new \InvalidArgumentException( 'Missing required info field: ' . PropertyInfoStore::KEY_DATA_TYPE );
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
	 * @see   PropertyInfoStore::removePropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @return bool
	 */
	public function removePropertyInfo( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

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