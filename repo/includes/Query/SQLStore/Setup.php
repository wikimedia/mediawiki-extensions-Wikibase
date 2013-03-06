<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\TableBuilder;
use Wikibase\Repo\Database\QueryInterface;
use Wikibase\Repo\Database\TableDefinition;
use Wikibase\Repo\Database\FieldDefinition;
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
	private function getTables() {
		// TODO: setup dv tables for different levels of snaks
		// TODO: setup id tracking tables
		// TODO: setup stats tables

		return array_merge( $this->getNonDVTables(), $this->getDVTables() );
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
			foreach ( array( 'msnak_', 'qualifier_' ) as $snakLevel ) {
				$table = $dataValueHandler->getTableDefinition();
				$table = $table->mutateName( $snakLevel . $table->getName() );

				$table = $table->mutateFields(
					array_merge(
						array(
							// Internal claim id
							new FieldDefinition(
								'claim_id',
								FieldDefinition::TYPE_INTEGER,
								FieldDefinition::NOT_NULL,
								FieldDefinition::NO_DEFAULT,
								FieldDefinition::ATTRIB_UNSIGNED,
								FieldDefinition::INDEX
							),

							// Internal property id
							new FieldDefinition(
								'property_id',
								FieldDefinition::TYPE_INTEGER,
								FieldDefinition::NOT_NULL,
								FieldDefinition::NO_DEFAULT,
								FieldDefinition::ATTRIB_UNSIGNED,
								FieldDefinition::INDEX
							),
						),
						$table->getFields()
					)
				);

				$dvTables[] = $table;
			}
		}

		return $dvTables;
	}

	/**
	 * TODO
	 *
	 * @return TableDefinition[]
	 */
	private function getNonDVTables() {
		$tables = array();

		// TODO: multi field indexes
		// TODO: more optimal types

		// Id map with Wikibase EntityId to internal SQL store id
		$tables[] = new TableDefinition(
			'entities',
			array(
				// Internal id
				new FieldDefinition(
					'id',
					FieldDefinition::TYPE_INTEGER,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::ATTRIB_UNSIGNED,
					FieldDefinition::INDEX_PRIMARY,
					FieldDefinition::AUTOINCREMENT
				),

				// EntityId type part
				new FieldDefinition(
					'type',
					FieldDefinition::TYPE_TEXT,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::NO_ATTRIB,
					FieldDefinition::INDEX
				),

				// EntityId numerical part
				new FieldDefinition(
					'number',
					FieldDefinition::TYPE_INTEGER,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::ATTRIB_UNSIGNED,
					FieldDefinition::INDEX
				),
			)
		);

		// Claim id table
		$tables[] = new TableDefinition(
			'claims',
			array(
				// Internal id
				new FieldDefinition(
					'id',
					FieldDefinition::TYPE_INTEGER,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::ATTRIB_UNSIGNED,
					FieldDefinition::INDEX_PRIMARY,
					FieldDefinition::AUTOINCREMENT
				),

				// External id
				new FieldDefinition(
					'guid',
					FieldDefinition::TYPE_TEXT,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::ATTRIB_UNSIGNED,
					FieldDefinition::INDEX
				),

				// Internal id of the claims subject
				new FieldDefinition(
					'subject_id',
					FieldDefinition::TYPE_INTEGER,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::ATTRIB_UNSIGNED,
					FieldDefinition::INDEX
				),

				// Internal id of the property of the main snak
				new FieldDefinition(
					'property_id',
					FieldDefinition::TYPE_INTEGER,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::ATTRIB_UNSIGNED,
					FieldDefinition::INDEX
				),

				// Rank
				new FieldDefinition(
					'rank',
					FieldDefinition::TYPE_INTEGER,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::ATTRIB_UNSIGNED,
					FieldDefinition::INDEX
				),

				// Hash
				new FieldDefinition(
					'hash',
					FieldDefinition::TYPE_TEXT,
					FieldDefinition::NOT_NULL,
					FieldDefinition::NO_DEFAULT,
					FieldDefinition::NO_ATTRIB,
					FieldDefinition::INDEX
				),
			)
		);

		return $tables;
	}

	/**
	 * Returns the provided table with the configs table prefix prepended to the name of the table.
	 *
	 * @since wd.qe
	 *
	 * @param TableDefinition $tableDefinition
	 *
	 * @return TableDefinition
	 */
	private function getPrefixedTable( TableDefinition $tableDefinition ) {
		return $tableDefinition->mutateName( $this->config->getTablePrefix() . $tableDefinition->getName() );
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

		foreach ( $this->getTables() as $table ) {
			$table = $this->getPrefixedTable( $table );
			$success = $this->tableBuilder->createTable( $table ) && $success;
		}

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

		foreach ( $this->getTables() as $table ) {
			$table = $this->getPrefixedTable( $table );
			$success = $this->queryInterface->dropTable( $table->getName() ) && $success;
		}

		return $success;
	}

}
