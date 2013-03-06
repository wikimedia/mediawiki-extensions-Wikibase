<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\TableBuilder;
use Wikibase\Repo\Database\QueryInterface;
use Wikibase\Repo\Database\TableDefinition;
use MessageReporter;

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
 * @since wd.qe
 *
 * @file
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Setup {

	/**
	 * @since wd.qe
	 *
	 * @var StoreConfig
	 */
	private $config;

	/**
	 * @since wd.qe
	 *
	 * @var QueryInterface
	 */
	private $queryInterface;

	/**
	 * @since wd.qe
	 *
	 * @var TableBuilder
	 */
	private $tableBuilder;

	/**
	 * @since wd.qe
	 *
	 * @var MessageReporter|null
	 */
	private $messageReporter;

	/**
	 * @since wd.qe
	 *
	 * @param StoreConfig $storeConfig
	 * @param QueryInterface $queryInterface
	 * @param TableBuilder $tableBuilder
	 * @param MessageReporter|null $messageReporter
	 */
	public function __construct( StoreConfig $storeConfig, QueryInterface $queryInterface,
								 TableBuilder $tableBuilder, MessageReporter $messageReporter = null ) {
		$this->config = $storeConfig;
		$this->tableBuilder = $tableBuilder;
		$this->queryInterface = $queryInterface;
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since wd.qe
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
	 * @since wd.qe
	 *
	 * @return boolean Success indicator
	 */
	public function install() {
		$this->report( 'Starting install of ' . $this->config->getStoreName() );

		$success = $this->setupTables();

		// TODO: initialize basic content

		$this->report( 'Finished install of ' . $this->config->getStoreName() );

		return $success;
	}

	/**
	 * TODO
	 *
	 * @return TableDefinition[]
	 */
	private function getDVTables() {
		$dvTables = array();

		/**
		 * @var DataValueHandler $dataValueHandler
		 */
		foreach ( $this->config->getDataValueHandlers() as $dataValueHandler ) {
			$table = $dataValueHandler->getTableDefinition();
			$table = $table->getClone( $this->config->getTablePrefix() . $table->getName() );

			$dvTables[] = $table;
		}

		return $dvTables;
	}

	/**
	 * Sets up the tables of the store.
	 *
	 * @since wd.qe
	 *
	 * @return boolean Success indicator
	 */
	private function setupTables() {
		$success = true;

		foreach ( $this->getDVTables() as $table ) {
			$success = $this->tableBuilder->createTable( $table ) && $success;
		}

		// TODO: setup dv tables for different levels of snaks
		// TODO: setup id tracking tables
		// TODO: setup stats tables

		return $success;
	}

	/**
	 * Uninstall the store.
	 *
	 * @since wd.qe
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
	 * @since wd.qe
	 *
	 * @return boolean Success indicator
	 */
	private function dropTables() {
		$success = true;

		/**
		 * @var DataValueHandler $dataValueHandler
		 */
		foreach ( $this->getDVTables() as $table ) {
			$success = $this->queryInterface->dropTable( $table->getName() ) && $success;
		}

		// TODO: match changes to setup TODOs

		return $success;
	}

}
