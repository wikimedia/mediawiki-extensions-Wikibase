<?php

namespace Wikibase\QueryEngine\SQLStore;

use MessageReporter;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\QueryInterface;
use Wikibase\Database\QueryInterfaceException;
use Wikibase\Database\TableBuilder;
use Wikibase\Database\TableDefinition;

/**
 * Setup for the SQLStore.
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
class Setup {

	/**
	 * @since 0.1
	 *
	 * @var StoreConfig
	 */
	private $config;

	/**
	 * @since 0.1
	 *
	 * @var QueryInterface
	 */
	private $queryInterface;

	/**
	 * @since 0.1
	 *
	 * @var TableBuilder
	 */
	private $tableBuilder;

	/**
	 * @since 0.1
	 *
	 * @var MessageReporter|null
	 */
	private $messageReporter;

	/**
	 * @since 0.1
	 *
	 * @var Schema
	 */
	private $storeSchema;

	/**
	 * @since 0.1
	 *
	 * @param StoreConfig $storeConfig
	 * @param Schema $storeSchema
	 * @param QueryInterface $queryInterface
	 * @param TableBuilder $tableBuilder
	 * @param MessageReporter|null $messageReporter
	 */
	public function __construct( StoreConfig $storeConfig, Schema $storeSchema, QueryInterface $queryInterface,
								 TableBuilder $tableBuilder, MessageReporter $messageReporter = null ) {
		$this->config = $storeConfig;
		$this->storeSchema = $storeSchema;
		$this->tableBuilder = $tableBuilder;
		$this->queryInterface = $queryInterface;
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $message
	 */
	private function report( $message ) {
		if ( $this->messageReporter !== null ) {
			$this->messageReporter->reportMessage( $message );
		}
	}

	/**
	 * Install the store.
	 *
	 * @since 0.1
	 */
	public function install() {
		$this->report( 'Starting install of ' . $this->config->getStoreName() );

		try {
			$this->setupTables();
		}
		catch ( QueryInterfaceException $exception ) {
			// TODO: throw exception of proper type
		}

		// TODO: initialize basic content

		$this->report( 'Finished install of ' . $this->config->getStoreName() );
	}

	/**
	 * Sets up the tables of the store.
	 *
	 * @since 0.1
	 */
	private function setupTables() {
		foreach ( $this->storeSchema->getTables() as $table ) {
			$this->tableBuilder->createTable( $table );
		}
	}

	/**
	 * Uninstall the store.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	public function uninstall() {
		$this->report( 'Starting uninstall of ' . $this->config->getStoreName() );

		$success = $this->dropTables();

		$this->report( 'Finished uninstall of ' . $this->config->getStoreName() );

		return $success;
	}

	/**
	 * Removes the tables belonging to the store.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	private function dropTables() {
		$success = true;

		foreach ( $this->storeSchema->getTables() as $table ) {
			$success = $this->queryInterface->dropTable( $table->getName() ) && $success;
		}

		return $success;
	}

}
