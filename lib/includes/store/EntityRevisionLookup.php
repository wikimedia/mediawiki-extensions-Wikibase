<?php

namespace Wikibase;

/**
 * Contains methods for interaction with an entity store.
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
 * @author Daniel Kinzler
 */
interface EntityRevisionLookup {

	/**
	 * Returns the entity revision with the provided id or null if there is no such
	 * entity. If a $revision is given, the requested revision of the entity is loaded.
	 * If that revision does not exist or does not belong to the given entity,
	 * an exception is thrown.
	 *
	 * @since 0.4
	 *
	 * @param EntityID $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @return EntityRevision|null
	 * @throw StorageException
	 */
	public function getEntityRevision( EntityID $entityId, $revision = 0 );

	/**
	 * Fetches the entity revisions with provided ids and returns them.
	 * The result array contains the prefixed entity ids as keys.
	 * The values are either an EntityRevision or null, if there is no entity with the associated id.
	 *
	 * @since 0.4
	 *
	 * @param EntityID[] $entityIds
	 *
	 * @return EntityRevision|null[]
	 */
	//public function getEntityRevisions( array $entityIds );

}
