<?php

namespace Wikibase;

/**
 * Contains methods for interaction with the entity cache.
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
 */
interface EntityCache {

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
	public function updateEntity( Entity $entity );

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
	public function addEntity( Entity $entity );

	/**
	 * Returns if there currently is an entry in the cache for the provided entity.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean
	 */
	public function hasEntity( Entity $entity );

	/**
	 * Removes the provided entity from the cache (if present).
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function deleteEntity( Entity $entity );

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
	public function getEntity( $entityType, $entityId );

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
	public function getItem( $itemId );

}