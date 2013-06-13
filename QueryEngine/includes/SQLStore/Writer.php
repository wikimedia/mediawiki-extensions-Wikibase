<?php

namespace Wikibase\QueryEngine\SQLStore;

use Wikibase\Claim;
use Wikibase\Database\QueryInterface;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\QueryEngine\QueryStoreWriter;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimInserter;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimRowBuilder;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimsTable;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakInserter;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakRowBuilder;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakStore;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValueSnakStore;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakRow;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakStore;
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
class Writer implements QueryStoreWriter {

	private $entityInserter;
	private $entityUpdater;
	private $entityRemover;

	public function __construct( EntityInserter $inserter, EntityUpdater $updater, EntityRemover $remover ) {
		$this->entityInserter = $inserter;
		$this->entityUpdater = $updater;
		$this->entityRemover = $remover;
	}

	/**
	 * @see QueryStoreUpdater::insertEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function insertEntity( Entity $entity ) {
		$this->entityInserter->insertEntity( $entity );
	}

	/**
	 * @see QueryStoreUpdater::updateEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function updateEntity( Entity $entity ) {
		$this->entityUpdater->updateEntity( $entity );
	}

	/**
	 * @see QueryStoreUpdater::deleteEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function deleteEntity( Entity $entity ) {
		$this->entityRemover->removeEntity( $entity );
	}

}
