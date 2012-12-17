<?php

namespace Wikibase;

use MWException;

/**
 * Interface for Entity loaders.
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
interface EntityLoader {

	/**
	 * Fetches and returns the specified entity, or null if there is no entity with the provided id.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return Entity|null
	 */
	public function getEntity( EntityId $entityId );

	/**
	 * Fetches the entities with provided ids and returns them.
	 * The result array contains the prefixed entity ids as keys.
	 * The values are either an Entity or null, if there is no entity with the associated id.
	 *
	 * @since 0.4
	 *
	 * @param array $entityIds
	 *
	 * @return Entity|null[]
	 */
	public function getEntities( array $entityIds );

}
