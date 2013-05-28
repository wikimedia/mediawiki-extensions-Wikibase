<?php

namespace Wikibase\QueryEngine\SQLStore;

use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimInserter;

/**
 * Use case for inserting entities into the store.
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
class EntityInserter {

	private $entityTable;
	private $claimInserter;
	private $idFinder;

	/**
	 * @since 0.1
	 *
	 * @param EntityTable $entityTable
	 * @param ClaimInserter $claimInserter
	 */
	public function __construct( EntityTable $entityTable, ClaimInserter $claimInserter, InternalEntityIdFinder $idFinder ) {
		$this->entityTable = $entityTable;
		$this->claimInserter = $claimInserter;
		$this->idFinder = $idFinder;
	}

	/**
	 * @see QueryStoreUpdater::insertEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function insertEntity( Entity $entity ) {
		$this->entityTable->insertEntity( $entity );

		$internalSubjectId = $this->getInternalId( $entity->getId() );

		foreach ( $entity->getClaims() as $claim ) {
			$this->claimInserter->insertClaim(
				$claim,
				$internalSubjectId
			);
		}

		// TODO: obtain and insert virtual claims
	}

	protected function getInternalId( EntityId $entityId ) {
		return $this->idFinder->getInternalIdForEntity(
			$entityId->getEntityType(),
			$entityId->getNumericId()
		);
	}

}
