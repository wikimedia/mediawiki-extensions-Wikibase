<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\FieldDefinition;
use Wikibase\Repo\Database\TableDefinition;

/**
 * Schema configuration for the SQLStore.
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

class Schema {

	/**
	 * @since wd.qe
	 *
	 * @var StoreConfig
	 */
	private $config;

	/**
	 * The DataValueHandlers for the DataValue types supported by this configuration.
	 * Array keys are snak types pointing to arrays where array keys are DataValue type
	 * identifiers (string) pointing to the corresponding DataValueHandler.
	 *
	 * @since wd.qe
	 *
	 * @var DataValueHandler[]
	 */
	private $dvHandlers = array();

	/**
	 * int => str
	 *
	 * @since wd.qe
	 *
	 * @var string[]
	 */
	private $snakTypes;

	/**
	 * @since wd.qe
	 *
	 * @var boolean
	 */
	private $initialized = false;

	/**
	 * @since wd.qe
	 *
	 * @param StoreConfig $config
	 */
	public function __construct( StoreConfig $config ) {
		$this->config = $config;

		$this->snakTypes = array(
			StoreConfig::SNAK_MAIN => 'mainsnak_',
			StoreConfig::SNAK_QUALIFIER => 'qualifier_',
		);
	}

	/**
	 * @since wd.qe
	 *
	 * @return TableDefinition[]
	 */
	public function getTables() {
		$this->initialize();

		return array();
		// TODO
	}

	/**
	 * @since wd.qe
	 *
	 * @param string $dataType
	 * @param int $snakType
	 *
	 * @return DataValueHandler
	 */
	public function getDataValueHandler( $dataType, $snakType ) {
		$this->initialize();

		return array();
		// TODO
	}

	/**
	 * @since wd.qe
	 */
	private function initialize() {
		if ( $this->initialized ) {
			return;
		}

		$this->expandDataValueHandlers();

		$this->initialized = true;
	}

	/**
	 * Turns the list of DataValueHandler objects into a list of these objects per snak type.
	 * The table names are prefixed with both the stores table prefix and the snak type specific one.
	 * Additional fields required by the store are also added to the tables.
	 *
	 * @since wd.qe
	 */
	private function expandDataValueHandlers() {
		foreach ( $this->snakTypes as $snakType => $snakTablePrefix ) {
			$handlers = array();

			foreach ( $this->config->getDataValueHandlers() as $dataValueType => $dataValueHandler ) {
				$dvTable = $dataValueHandler->getDataValueTable();

				$table = $dvTable->getTableDefinition();
				$table = $table->mutateName( $this->config->getTablePrefix() . $snakTablePrefix . $table->getName() );
				$table = $table->mutateFields(
					array_merge(
						$this->getPropertySnakFields(),
						$table->getFields()
					)
				);

				$dvTable = $dvTable->mutateTableDefinition( $table );
				$dataValueHandler = $dataValueHandler->mutateDataValueTable( $dvTable );

				$handlers[$dataValueType] = $dataValueHandler;
			}

			$this->dvHandlers[$snakType] = $handlers;
		}
	}

	/**
	 * @since wd.qe
	 *
	 * @return FieldDefinition[]
	 */
	private function getPropertySnakFields() {
		return array(
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
		);
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
			$snakLevels = array(
				$this->config->getMainSnakPrefix(),
				$this->config->getQualifierPrefix(),
			);

			foreach ( $snakLevels as $snakLevel ) {
				$table = $dataValueHandler->getTableDefinition();
				$table = $table->mutateName( $snakLevel . $table->getName() );

				$table = $table->mutateFields(
					array_merge(
						$this->getPropertySnakFields(),
						$table->getFields()
					)
				);

				$dvTables[] = $table;
			}
		}

		return $dvTables;
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
	 * @since wd.qe
	 *
	 * @return DataValueHandler[]
	 */
	public function getDataValueHandlers() {
		return $this->dvHandlers;
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

		// Table for snaks without a value
		$tables[] = new TableDefinition(
			'valueless_snaks',
			array_merge(
				$this->getPropertySnakFields(),
				array(
					// Type of the snak
					new FieldDefinition(
						'type',
						FieldDefinition::TYPE_INTEGER,
						FieldDefinition::NOT_NULL,
						FieldDefinition::NO_DEFAULT,
						FieldDefinition::ATTRIB_UNSIGNED,
						FieldDefinition::INDEX
					),

					// Level at which the snak is used (ie "main snak" or "qualifier")
					new FieldDefinition(
						'level',
						FieldDefinition::TYPE_INTEGER,
						FieldDefinition::NOT_NULL,
						FieldDefinition::NO_DEFAULT,
						FieldDefinition::ATTRIB_UNSIGNED,
						FieldDefinition::INDEX
					),
				)
			)
		);

		return $tables;
	}

}