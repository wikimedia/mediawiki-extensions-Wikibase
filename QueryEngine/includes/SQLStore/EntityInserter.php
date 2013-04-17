<?php

namespace Wikibase\QueryEngine\SQLStore;

use Wikibase\Database\QueryInterface;
use Wikibase\Entity;
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

	private $queryInterface;
	private $entityTableName;
	private $claimInserter;

	/**
	 * @since 0.1
	 *
	 * @param QueryInterface $queryInterface
	 * @param string $entityTableName
	 * @param ClaimInserter $claimInserter
	 */
	public function __construct( QueryInterface $queryInterface, $entityTableName, ClaimInserter $claimInserter ) {
		$this->queryInterface = $queryInterface;
		$this->entityTableName = $entityTableName;
		$this->claimInserter = $claimInserter;
	}

	/**
	 * @see QueryStoreUpdater::insertEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function insertEntity( Entity $entity ) {
		$internalEntityId = $this->insertIntoEntitiesTable( $entity );

		foreach ( $entity->getClaims() as $claim ) {
			$this->claimInserter->insertClaim( $claim, $internalEntityId );
		}

		// TODO: obtain and insert virtual claims
	}


	private function insertIntoEntitiesTable( Entity $entity ) {
		$this->queryInterface->insert(
			$this->entityTableName,
			array(
				'type' => $entity->getType(),
				'number' => $entity->getId()->getNumericId(),
			)
		);

		return $this->queryInterface->getInsertId();
	}

}
