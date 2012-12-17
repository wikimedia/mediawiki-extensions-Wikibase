<?php

namespace Wikibase;
use MWException;

/**
 * Implementation of EntityLoader that caches the obtained entities in memory.
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
 */
class CachingEntityLoader implements EntityLoader {

	/**
	 * @var Entity|null[]
	 */
	protected $loadedEntities = array();

	/**
	 * @see EntityLoader::getEntity
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return Entity|null
	 */
	public function getEntity( EntityId $entityId ) {
		if ( !array_key_exists( $entityId->getPrefixedId(), $this->loadedEntities ) ) {
			$content = EntityContentFactory::singleton()->getFromId( $entityId );

			if ( $content === null ) {
				$this->setNonExistingEntity( $entityId );
			}
			else {
				$this->setEntities( array( $content->getEntity() ) );
			}
		}

		return $this->loadedEntities[$entityId->getPrefixedId()];
	}

	/**
	 * @see EntityLoader::getEntities
	 *
	 * @since 0.4
	 *
	 * @param array $entityIds
	 *
	 * @return Entity|null[]
	 */
	public function getEntities( array $entityIds ) {
		$loadedProps = array();
		$propsToLoad = array();

		foreach ( $entityIds as $entityId ) {
			if ( !( $entityId instanceof EntityId ) ) {
				throw new MWException( 'All entity ids passed to loadEntities must be an instance of EntityId' );
			}

			$prefixedId = $entityId->getPrefixedId();

			if ( array_key_exists( $prefixedId, $this->loadedEntities ) ) {
				$loadedProps[$prefixedId] = $this->loadedEntities[$prefixedId];
			}
			else {
				$propsToLoad[] = $entityId;
			}
		}

		// TODO: we really want batch lookup here :)
		foreach ( $propsToLoad as $entityId ) {
			$loadedProps[$entityId->getPrefixedId] = $this->getEntity( $entityId );
		}

		return $loadedProps;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 */
	protected function setNonExistingEntity( EntityId $entityId ) {
		$this->loadedEntities[$entityId->getPrefixedId()] = null;
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

			$this->loadedEntities[$entity->getPrefixedId()] = $entity;
		}
	}

}
