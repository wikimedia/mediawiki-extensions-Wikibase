<?php

namespace Wikibase\QueryEngine\SQLStore;

use Wikibase\Database\QueryInterface;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimInserter;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimRowBuilder;
use Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimsTable;
use Wikibase\QueryEngine\SQLStore\Engine\DescriptionMatchFinder;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakInserter;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakRowBuilder;
use Wikibase\QueryEngine\SQLStore\SnakStore\SnakStore;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakStore;
use Wikibase\QueryEngine\SQLStore\SnakStore\ValueSnakStore;
use Wikibase\SnakRole;

/**
 * SQLStore component factory.
 * This class is private to the SQLStore component and should not be access from the outside.
 * It is furthermore intended to contain construction logic needed by the Store class while
 * it should not be publicly exposed there. This factory should thus not be passed to or
 * constructed in deeper parts of the SQLStore.
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
final class Factory {

	protected $config;
	protected $queryInterface;

	/**
	 * @var Schema|null
	 */
	protected $schema = null;

	public function __construct( StoreConfig $config, QueryInterface $queryInterface ) {
		$this->config = $config;
		$this->queryInterface = $queryInterface;
	}

	/**
	 * @return Schema
	 */
	public function getSchema() {
		if ( $this->schema === null ) {
			$this->schema = new Schema( $this->config );
		}

		return $this->schema;
	}

	public function newEntityInserter() {
		return new EntityInserter(
			$this->newEntityTable(),
			$this->newClaimInserter(),
			$this->getInternalEntityIdTransformer()
		);
	}

	public function newEntityTable() {
		return new EntityTable(
			$this->queryInterface,
			$this->getSchema()->getEntitiesTable()->getName()
		);
	}

	public function newClaimInserter() {
		return new ClaimInserter(
			$this->newClaimsTable(),
			$this->newSnakInserter(),
			new ClaimRowBuilder( $this->getInternalEntityIdTransformer() )
		);
	}

	public function newClaimsTable() {
		return new ClaimsTable(
			$this->queryInterface,
			$this->getSchema()->getClaimsTable()->getName()
		);
	}

	public function newSnakInserter() {
		return new SnakInserter(
			$this->getSnakStores(),
			new SnakRowBuilder( $this->getInternalEntityIdTransformer() )
		);
	}

	/**
	 * @return SnakStore[]
	 */
	protected function getSnakStores() {
		return array(
			new ValueSnakStore(
				$this->queryInterface,
				$this->getSchema()->getDataValueHandlers( SnakRole::MAIN_SNAK ),
				SnakRole::MAIN_SNAK
			),
			new ValueSnakStore(
				$this->queryInterface,
				$this->getSchema()->getDataValueHandlers( SnakRole::QUALIFIER ),
				SnakRole::QUALIFIER
			),
			new ValuelessSnakStore(
				$this->queryInterface,
				$this->getSchema()->getValuelessSnaksTable()->getName()
			)
		);
	}

	/**
	 * @return InternalEntityIdTransformer
	 */
	protected function getInternalEntityIdTransformer() {
		return new SimpleEntityIdTransformer( $this->config->getEntityTypeMap() );
	}

	public function newWriter() {
		return new Writer(
			$this->newEntityInserter()
		);
	}

	/**
	 * @return DescriptionMatchFinder
	 */
	public function newDescriptionMatchFinder() {
		return new DescriptionMatchFinder(
			$this->queryInterface,
			$this->schema,
			$this->config->getPropertyDataValueTypeLookup(),
			$this->getInternalEntityIdTransformer()
		);
	}

}
