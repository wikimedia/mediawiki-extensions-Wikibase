<?php

namespace Wikibase;
use ORMTable;

/**
 * Class representing the wbc_entity_cache table.
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
		$identifiers = array(
			'entity_id' => $entity->getId(),
			'entity_type' => $entity->getType(),
		);

		$cachedEntity = $this->newRow( array(
			'entity_data' => $entity->toArray(),
		) );

		$currentId = $this->selectFieldsRow( 'id', $identifiers );

		if ( $currentId === false ) {
			$cachedEntity->setFields( $identifiers );
		}
		else {
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
		return $this->newRow( array(
			'entity_id' => $entity->getId(),
			'entity_type' => $entity->getType(),
			'entity_data' => $entity->toArray(),
		) )->save( __METHOD__ );
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

}
