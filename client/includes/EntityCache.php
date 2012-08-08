<?php

namespace Wikibase;
use ORMTable;

/**
 * Represents the entity cache of a single cluster.
 * Corresponds to the wbc_entity_cache table.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityCache extends ORMTable {

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
		$cachedEntity = $this->newRowFromEntity( $entity );
		$currentId = $this->getCacheIdForEntity( $entity );

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
	 * @param Entity $entity
	 *
	 * @return integer|false
	 */
	public function getCacheIdForEntity( Entity $entity ) {
		$identifiers = array(
			'entity_id' => $entity->getId(),
			'entity_type' => $entity->getType(),
		);

		return $this->selectFieldsRow( 'id', $identifiers );
	}

	/**
	 * Returns if there currently is an entry in the cache for the provided entity.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean
	 */
	public function hasEntity( Entity $entity ) {
		return $this->getCacheIdForEntity( $entity ) !== false;
	}

	/**
	 * Removes the provided entity from the cache (if present).
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function deleteEntity( Entity $entity ) {
		return $this->delete( array(
			'entity_id' => $entity->getId(),
			'entity_type' => $entity->getType(),
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
	public function newRowFromEntity( Entity $entity ) {
		return $this->newRow( array(
			'entity_id' => $entity->getId(),
			'entity_type' => $entity->getType(),
			'entity_data' => $entity,
		) );
	}

	/**
	 * Returns the entity with provided type and entity id or false is there is no such
	 * entity in the cache.
	 *
	 * @since 0.1
	 *
	 * @param string $entityType
	 * @param integer $entityId
	 *
	 * @return boolean|Entity
	 */
	public function getEntity( $entityType, $entityId ) {
		$cachedEntity = $this->selectRow( null, array(
			'entity_type' => $entityType,
			'entity_id' => $entityId,
		) );

		return $cachedEntity === false ? $cachedEntity : $cachedEntity->getEntity();
	}

	/**
	 * Returns the item with provided item id or false is there is no such
	 * item in the cache.
	 *
	 * @since 0.1
	 *
	 * @param integer $itemId
	 *
	 * @return boolean|Item
	 */
	public function getItem( $itemId ) {
		return $this->getEntity( Item::ENTITY_TYPE, $itemId );
	}

	/**
	 * @see ORMTable::singleton
	 * Overload boilerplate for type hinting.
	 *
	 * @since 0.1
	 *
	 * @return EntityCache
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

}
