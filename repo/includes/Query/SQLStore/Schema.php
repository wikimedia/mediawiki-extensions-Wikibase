<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;
use Wikibase\SnakRole;
use OutOfRangeException;
use OutOfBoundsException;

/**
 * Contains the tables and table interactors for a given SQLStore configuration.
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
	 * @var array[]
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
			SnakRole::MAIN_SNAK => 'mainsnak_',
			SnakRole::QUALIFIER => 'qualifier_',
		);
	}

	/**
	 * Returns all tables part of the stores schema.
	 *
	 * @since wd.qe
	 *
	 * @return TableDefinition[]
	 */
	public function getTables() {
		$this->initialize();

		return array_merge(
			$this->getNonDvTables(),
			$this->getDvTables()
		);
	}

	/**
	 * Returns the DataValueHandler for a given DataValue type and SnakRole.
	 *
	 * @since wd.qe
	 *
	 * @param string $dataValueType
	 * @param int $snakRole
	 *
	 * @return DataValueHandler
	 * @throws OutOfRangeException
	 * @throws OutOfBoundsException
	 */
	public function getDataValueHandler( $dataValueType, $snakRole ) {
		$this->initialize();

		if ( !array_key_exists( $snakRole, $this->dvHandlers ) ) {
			throw new OutOfRangeException( 'Got an unsupported snak role' );
		}

		if ( !array_key_exists( $dataValueType, $this->dvHandlers[$snakRole] ) ) {
			throw new OutOfBoundsException(
				'Requested a DataValuerHandler for DataValue type '
					. "'$dataValueType' while no handler for this type is set"
			);
		}

		return $this->dvHandlers[$snakRole][$dataValueType];
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
	 * TODO
	 *
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
	 * @since wd.qe
	 *
	 * @return TableDefinition[]
	 */
	private function getDvTables() {
		$tables = array();

		foreach ( $this->dvHandlers as $dvHandlers ) {
			/**
			 * @var DataValueHandler $dvHandler
			 */
			foreach ( $dvHandlers as $dvHandler ) {
				$tables[] = $dvHandler->getDataValueTable()->getTableDefinition();
			}
		}

		return $tables;
	}

	/**
	 * TODO
	 *
	 * @since wd.qe
	 *
	 * @return TableDefinition[]
	 */
	private function getNonDvTables() {
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