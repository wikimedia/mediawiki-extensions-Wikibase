<?php

namespace Wikibase\QueryEngine\SQLStore;

use OutOfBoundsException;

/**
 * Map from external entity ids to internal entity ids.
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
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdMap implements InternalEntityIdFinder {

	protected $ids = array();

	/**
	 * @see InternalEntityIdFinder::getInternalIdForEntity
	 *
	 * @param string $entityType
	 * @param int $entityNumber
	 *
	 * @return int
	 * @throws OutOfBoundsException
	 */
	public function getInternalIdForEntity( $entityType, $entityNumber ) {
		$idIsSet = array_key_exists( $entityType, $this->ids )
			&& array_key_exists( $entityNumber, $this->ids[$entityType] );

		if ( !$idIsSet ) {
			throw new OutOfBoundsException( 'The requested id is not present in the EntityIdMap' );
		}

		return $this->ids[$entityType][$entityNumber];
	}

	/**
	 * @param string $entityType
	 * @param int $entityNumber
	 * @param int $internalId
	 */
	public function addId( $entityType, $entityNumber, $internalId ) {
		if ( !array_key_exists( $entityType, $this->ids ) ) {
			$this->ids[$entityType] = array();
		}

		$this->ids[$entityType][$entityNumber] = $internalId;
	}


}
