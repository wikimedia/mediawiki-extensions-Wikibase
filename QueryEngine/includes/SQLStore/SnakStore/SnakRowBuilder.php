<?php

namespace Wikibase\QueryEngine\SQLStore\SnakStore;

use InvalidArgumentException;
use Wikibase\EntityId;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\QueryEngine\SQLStore\InternalEntityIdTransformer;
use Wikibase\Snak;

/**
 * Builder for ClaimRow objects.
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
class SnakRowBuilder {

	protected $idFinder;

	public function __construct( InternalEntityIdTransformer $idFinder ) {
		$this->idFinder = $idFinder;
	}

	/**
	 * @since 0.1
	 *
	 * @param Snak $snak
	 * @param int $snakRole
	 * @param int $internalClaimId
	 * @param int $internalSubjectId
	 *
	 * @return SnakRow
	 * @throws InvalidArgumentException
	 */
	public function newSnakRow( Snak $snak, $snakRole, $internalClaimId, $internalSubjectId ) {
		if ( $snak instanceof PropertyValueSnak ) {
			return $this->newValueSnakRow( $snak, $snakRole, $internalClaimId, $internalSubjectId );
		}

		if ( $snak instanceof PropertySomeValueSnak || $snak instanceof PropertyNoValueSnak ) {
			return $this->newValuelessSnakRow( $snak, $snakRole, $internalClaimId, $internalSubjectId );
		}

		throw new InvalidArgumentException( 'Got a snak type no supported by the SnakRowBuilder' );
	}

	protected function newValueSnakRow( PropertyValueSnak $snak, $snakRole, $internalClaimId, $internalSubjectId ) {
		return new ValueSnakRow(
			$snak->getDataValue(),
			$this->getInternalIdFor( $snak->getPropertyId() ),
			$internalClaimId,
			$snakRole,
			$internalSubjectId
		);
	}

	protected function newValuelessSnakRow( Snak $snak, $snakRole, $internalClaimId, $internalSubjectId ) {
		$internalSnakType = $snak instanceof PropertySomeValueSnak
			? ValuelessSnakRow::TYPE_SOME_VALUE : ValuelessSnakRow::TYPE_NO_VALUE;

		return new ValuelessSnakRow(
			$internalSnakType,
			$this->getInternalIdFor( $snak->getPropertyId() ),
			$internalClaimId,
			$snakRole,
			$internalSubjectId
		);
	}

	protected function getInternalIdFor( EntityId $entityId ) {
		return $this->idFinder->getInternalIdForEntity(
			$entityId->getEntityType(),
			$entityId->getNumericId()
		);
	}

}