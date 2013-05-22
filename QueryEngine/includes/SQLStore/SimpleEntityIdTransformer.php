<?php

namespace Wikibase\QueryEngine\SQLStore;

/**
 * Transforms entity types and numbers into internal store ids.
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
 * @author Denny Vrandecic
 */
class SimpleEntityIdTransformer implements InternalEntityIdTransformer {

	protected $idMap;

	/**
	 * @param int[] $idMap Maps entity types (strings) to a unique one digit integer
	 */
	public function __construct( array $idMap ) {
		$this->idMap = $idMap;
	}

	/**
	 * @see InternalEntityIdTransformer::getInternalIdForEntity
	 *
	 * @param string $entityType
	 * @param int $entityNumber
	 *
	 * @return int
	 */
	public function getInternalIdForEntity( $entityType, $entityNumber ) {
		$this->ensureEntityTypeIsKnown( $entityType );

		return $this->getComputedId( $entityType, $entityNumber );
	}

	protected function ensureEntityTypeIsKnown( $entityType ) {
		if ( !array_key_exists( $entityType, $this->idMap ) ) {
			throw new \OutOfBoundsException( "Id of unknown entity type '$entityType' cannot be transformed" );
		}
	}

	protected function getComputedId( $entityType, $entityNumber ) {
		return $entityNumber * 10 + $this->idMap[$entityType];
	}

}
