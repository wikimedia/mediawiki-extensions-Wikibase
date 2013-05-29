<?php

namespace Wikibase\QueryEngine\SQLStore;

use OutOfBoundsException;
use Wikibase\EntityId;

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
class EntityIdTransformer implements InternalEntityIdFinder, InternalEntityIdInterpreter {

	protected $stringTypeToInt;
	protected $intTypeToString;

	/**
	 * @param int[] $idMap Maps entity types (strings) to a unique one digit integer
	 */
	public function __construct( array $idMap ) {
		$this->stringTypeToInt = $idMap;
	}

	/**
	 * @see InternalEntityIdFinder::getInternalIdForEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @return int
	 */
	public function getInternalIdForEntity( EntityId $entityId ) {
		$this->ensureEntityStringTypeIsKnown( $entityId->getEntityType() );

		return $this->getComputedId( $entityId );
	}

	protected function ensureEntityStringTypeIsKnown( $entityType ) {
		if ( !array_key_exists( $entityType, $this->stringTypeToInt ) ) {
			throw new OutOfBoundsException( "Id of unknown entity type '$entityType' cannot be transformed" );
		}
	}

	protected function getComputedId( EntityId $entityId ) {
		return $entityId->getNumericId() * 10 + $this->stringTypeToInt[$entityId->getEntityType()];
	}

	/**
	 * @see InternalEntityIdInterpreter::getExternalIdForEntity
	 *
	 * @param int $internalEntityId
	 *
	 * @return EntityId
	 */
	public function getExternalIdForEntity( $internalEntityId ) {
		$this->buildIntToStringMap();

		$numericId = (int)floor( $internalEntityId / 10 );
		$typeId = $internalEntityId % 10;

		$this->ensureEntityIntTypeIsKnown( $typeId );
		$typeId = $this->intTypeToString[$typeId];

		return new EntityId( $typeId, $numericId );
	}

	protected function buildIntToStringMap() {
		if ( is_array( $this->intTypeToString ) ) {
			return;
		}

		$this->intTypeToString = array();

		foreach ( $this->stringTypeToInt as $string => $int ) {
			$this->intTypeToString[$int] = $string;
		}
	}

	protected function ensureEntityIntTypeIsKnown( $intType ) {
		if ( !array_key_exists( $intType, $this->intTypeToString ) ) {
			throw new OutOfBoundsException( "Id of unknown entity type '$intType' cannot be interpreted" );
		}
	}

}
