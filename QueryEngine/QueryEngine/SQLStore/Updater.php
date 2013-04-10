<?php

namespace Wikibase\QueryEngine\SQLStore;

use Wikibase\Claim;
use Wikibase\Database\QueryInterface;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\PropertyValueSnak;
use Wikibase\QueryEngine\QueryStoreUpdater;
use Wikibase\Snak;
use Wikibase\SnakRole;

/**
 * Class responsible for writing information to the SQLStore.
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
class Updater implements QueryStoreUpdater {

	/**
	 * @since 0.1
	 *
	 * @var Schema
	 */
	private $schema;

	/**
	 * @since 0.1
	 *
	 * @var QueryInterface
	 */
	private $queryInterface;

	/**
	 * @since 0.1
	 *
	 * @param Schema $schema
	 * @param QueryInterface $queryInterface
	 */
	public function __construct( Schema $schema, QueryInterface $queryInterface ) {
		$this->schema = $schema;
		$this->queryInterface = $queryInterface;
	}

	/**
	 * @see QueryStoreUpdater::insertEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function insertEntity( Entity $entity ) {
		// TODO: insert entity info into entities table
		// TODO: insert info of linked entities into entities table

		foreach ( $entity->getClaims() as $claim ) {
			$this->insertClaim( $entity->getId(), $claim );
		}

		// TODO: obtain and insert virtual claims
	}

	/**
	 * @since 0.1
	 *
	 * @param EntityId $entityId
	 * @param Claim $claim
	 */
	private function insertClaim( EntityId $entityId, Claim $claim ) {
		// TODO: insert claim info into claims table

		$this->insertSnak(
			$claim->getMainSnak(),
			SnakRole::MAIN_SNAK,
			42, // TODO
			9001 // TODO
		);

		foreach ( $claim->getQualifiers() as $qualifierSnak ) {
			$this->insertSnak(
				$qualifierSnak,
				SnakRole::QUALIFIER,
				42, // TODO
				9001 // TODO
			);
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param Snak $snak
	 * @param int $snakRole
	 * @param int $internalClaimId
	 * @param int $internalPropertyId
	 */
	private function insertSnak( Snak $snak, $snakRole, $internalClaimId, $internalPropertyId ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$this->insertPropertyValueSnak( $snak, $snakRole, $internalClaimId, $internalPropertyId );
		}


		// TODO
	}

	private function insertPropertyValueSnak( PropertyValueSnak $snak, $snakRole, $internalClaimId, $internalPropertyId ) {
		$dataValueHandler = $this->schema->getDataValueHandler(
			$snak->getDataValue()->getType(),
			$snakRole
		);

		// TODO
	}

	/**
	 * @see QueryStoreUpdater::updateEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function updateEntity( Entity $entity ) {
		// TODO
	}

	/**
	 * @see QueryStoreUpdater::deleteEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function deleteEntity( Entity $entity ) {
		// TODO
	}

	// TODO: write methods

}
