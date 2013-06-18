<?php

namespace Wikibase;
use MWException;

/**
 * Implementation of EntityLookup that caches the obtained entities in memory.
 * The cache is never invalidated or purged. There is no size limit.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 *
 * @todo: rename to CachingEntityLookup
 */
class CachingEntityLoader implements EntityLookup {

	/**
	 * @var Entity|bool|null[]
	 */
	protected $loadedEntities = array();

	/**
	 * @var EntityLookup
	 */
	protected $lookup;

	/**
	 * @param EntityLookup $lookup The Lookup to use to load entities.
	 */
	public function __construct( EntityLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * Returns a cache key for the given item ID and optional revision
	 *
	 * @param EntityId $entityId
	 * @param int|bool $revision
	 *
	 * @return string
	 */
	protected function getCacheKey( $entityId, $revision = 0 ) {
		$key = $entityId->getPrefixedId();

		if ( $revision ) {
			$key .= ( '@' . $revision );
		}

		return $key;
	}

	/**
	 * @since 0.4
	 * @see   EntityLookup::getEntity
	 *
	 * @param EntityID $entityId
	 * @param int      $revision The desired revision id, 0 means "current".
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityId $entityId, $revision = 0 ) {
		wfProfileIn( __METHOD__ );
		$key = $this->getCacheKey( $entityId, $revision );

		// $this->loadedEntities[$key] is null if the entity is known to not exist,
		// true if it is known to exist, and an Entity if it is loaded.
		// If $this->loadedEntities[$key] is true, we still need to load it.

		if ( !array_key_exists( $key, $this->loadedEntities )
			|| $this->loadedEntities[$key] === true ) {
			$entity = $this->lookup->getEntity( $entityId, $revision );
			$this->loadedEntities[$key] = $entity;
		}

		wfProfileOut( __METHOD__ );
		return $this->loadedEntities[$key];
	}

	/**
	 * See EntityLookup::hasEntity()
	 *
	 * @since 0.4
	 *
	 * @param EntityID $entityId
	 *
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		wfProfileIn( __METHOD__ );
		$key = $this->getCacheKey( $entityId );

		// $this->loadedEntities[$key] is null if the entity is known to not exist,
		// true if it is known to exist, and an Entity if it is loaded.

		if ( !array_key_exists( $key, $this->loadedEntities ) ) {
			$hasEntity = $this->lookup->hasEntity( $entityId );
			$this->loadedEntities[$key] = $hasEntity === true ? true : null;
		}

		wfProfileOut( __METHOD__ );
		return $this->loadedEntities[$key] !== null;
	}

	/**
	 * @see EntityLookup::getEntities
	 *
	 * @since 0.4
	 *
	 * @param array $entityIds
	 *
	 * @return Entity|null[]
	 * @throws \MWException
	 */
	public function getEntities( array $entityIds ) {
		wfProfileIn( __METHOD__ );

		$loaded = array();
		$toLoad = array();

		foreach ( $entityIds as $entityId ) {
			if ( !( $entityId instanceof EntityId ) ) {
				wfProfileOut( __METHOD__ );
				throw new MWException( 'All entity ids passed to loadEntities must be an instance of EntityId' );
			}

			$prefixedId = $entityId->getPrefixedId();
			$key = $this->getCacheKey( $entityId );

			if ( array_key_exists( $key, $this->loadedEntities ) ) {
				$loaded[$prefixedId] = $this->loadedEntities[$key];
			}
			else {
				$toLoad[] = $entityId;
			}
		}

		if ( $toLoad ) {
			$newlyLoaded = $this->lookup->getEntities( $toLoad );
			$loaded = array_merge( $loaded, $newlyLoaded );

			$this->loadedEntities = array_merge( $this->loadedEntities, $newlyLoaded );
		}

		wfProfileOut( __METHOD__ );
		return $loaded;
	}

	/**
	 * Removes any entity with the given combination of ID and revision from the cache
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param int|bool $revision
	 *
	 * @return void
	 */
	public function purgeEntity( EntityId $entityId, $revision = false ) {
		$key = $this->getCacheKey( $entityId, $revision );
		unset( $this->loadedEntities[$key] );
	}

	/**
	 * Adds the provided entities to the entity cache of the loader.
	 * If an entity is already set, it will be overridden by the new value.
	 *
	 * @since 0.4
	 *
	 * @param Entity[] $entities
	 *
	 * @throws MWException
	 */
	public function setEntities( array $entities ) {
		foreach ( $entities as $entity ) {
			if ( !( $entity instanceof Entity ) ) {
				throw new MWException( 'All entities passed to setEntities must be an instance of Entity' );
			}

			$key = $this->getCacheKey( $entity->getId() );
			$this->loadedEntities[$key] = $entity;
		}
	}

}
