<?php

namespace Wikibase;
use ORMTable;
use MWException;

/**
 * Represents the entity cache of a single cluster.
 * Corresponds to the wbc_entity_cache table.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 *
 * @todo: rename to EntityMirrorTable
 */
class EntityCacheTable extends ORMTable implements EntityCache {

	/**
	 * @see IORMTable::getName
	 * @since 0.1
	 * @return string
	 */
	public function getName() {
		return 'wbc_entity_cache';
	}

	/**
	 * @see IORMTable::getFieldPrefix
	 * @since 0.1
	 * @return string
	 */
	public function getFieldPrefix() {
		return 'ec_';
	}

	/**
	 * @see IORMTable::getRowClass
	 * @since 0.1
	 * @return string
	 */
	public function getRowClass() {
		return '\Wikibase\CachedEntity';
	}

	/**
	 * @see IORMTable::getFields
	 * @since 0.1
	 * @return array
	 */
	public function getFields() {
		return array(
			'id' => 'id',
			'entity_id' => 'int',
			'entity_type' => 'str',
			'entity_data' => 'blob',
		);
	}

	/**
	 * Updates the entity cache using the provided entity.
	 * If it's currently in the cache, it will be updated.
	 * If it's not, it will be inserted.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function updateEntity( Entity $entity ) {
		//FIXME: record revision ID!
		$cachedEntity = $this->newRowFromEntity( $entity );
		$currentId = $this->getCacheIdForEntity( $entity->getId() );

		if ( $currentId !== false ) {
			$cachedEntity->setId( $currentId );
		}

		return $cachedEntity->save( __METHOD__ );
	}

	/**
	 * Adds the provided entity to the cache.
	 * This function does not do any checks against the current cache contents,
	 * so if the entity already exists or some other constraint is violated,
	 * the insert will fail. Use @see updateEntity if you need checks.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function addEntity( Entity $entity ) {
		return $this->newRowFromEntity( $entity )->save( __METHOD__ );
	}

	/**
	 * Returns the id of the cache entry for the provided entity
	 * or false if there is no such entry.
	 *
	 * @since 0.1
	 *
	 * @param EntityId $id
	 *
	 * @return integer|bool
	 */
	protected function getCacheIdForEntity( EntityId $id ) {
		$identifiers = array(
			'entity_id' => $id->getNumericId(),
			'entity_type' => $id->getEntityType()
		);

		return $this->selectFieldsRow( 'id', $identifiers );
	}

	/**
	 * @see EntityCache::hasEntity
	 *
	 * @since 0.1
	 *
	 * @param EntityId $id
	 *
	 * @return boolean
	 */
	public function hasEntity( EntityId $id ) {
		return $this->getCacheIdForEntity( $id ) !== false;
	}

	/**
	 * @see EntityCache::deleteEntity
	 *
	 * @since 0.1
	 *
	 * @param EntityId $id the ID of the entity to deleted
	 *
	 * @return boolean Success indicator
	 */
	public function deleteEntity( EntityId $id ) {
		return $this->delete( array(
			'entity_id' => $id->getNumericId(),
			'entity_type' => $id->getEntityType(),
		), __METHOD__ );
	}

	/**
	 * Constructs and returns a new CachedEntity object based on the provided entity.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return CachedEntity
	 */
	protected function newRowFromEntity( Entity $entity ) {
		//FIXME: record revision ID!
		return $this->newRow( array(
			'entity_id' => $entity->getId()->getNumericId(),
			'entity_type' => $entity->getType(),
			'entity_data' => $entity,
		) );
	}

	/**
	 * @since 0.1
	 * @see   EntityLookup::getEntity
	 *
	 * @param EntityID $entityId
	 * @param int      $revision The desired revision id, 0 means "current".
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityID $entityId, $revision = 0 ) {
		$where = array(
			'entity_type' => $entityId->getEntityType(),
			'entity_id' => $entityId->getNumericId(),
		);

		if ( $revision !== 0 ) {
			//FIXME: this field does not yet exist in the database!
			$where['entity_revision'] = $revision;
		}

		$cachedEntity = $this->selectRow( null, $where );

		return $cachedEntity === false ? null : $cachedEntity->getEntity();
	}

	/**
	 * @see EntityLookup::getEntities
	 *
	 * @since 0.4
	 *
	 * @param EntityID[] $entityIds
	 * @param array|bool $revision
	 *
	 * @return Entity|null[]
	 */
	public function getEntities( array $entityIds, $revision = false ) {
		$entities = array();

		// TODO: we really want batch lookup here :)
		foreach ( $entityIds as $key => $entityId ) {
			$rev = $revision;

			if ( is_array( $rev ) ) {
				if ( !array_key_exists( $key, $rev ) ) {
					throw new MWException( '$entityId has no revision specified' );
				}

				$rev = $rev[$key];
			}

			$entities[$entityId->getPrefixedId()] = $this->getEntity( $entityId, $rev );
		}

		return $entities;
	}

	/**
	 * @see EntityCache::getItem
	 *
	 * @since 0.1
	 *
	 * @param EntityId $entityId  The entity's ID
	 * @param bool|int $revision  The desired Revision
	 *
	 * @return boolean|Item
	 */
	public function getItem( EntityID $entityId, $revision = false  ) {
		return $this->getEntity( $entityId, $revision );
	}

	/**
	 * @see EntityCache::clear
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear() {
		return wfGetDB( DB_MASTER )->delete( $this->getName(), '*', __METHOD__ );
	}
	public function rebuild() {

	}
}
