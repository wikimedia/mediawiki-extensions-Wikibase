<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\FieldDefinition;
use Wikibase\Repo\Database\FieldDefinition as FD;
use Wikibase\Repo\Database\TableDefinition;
use Wikibase\Repo\Query\SQLStore\DataValueTable;
use InvalidArgumentException;

/**
 * A collection of DataValueTable objects to be used by the store.
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
final class DataValueTables {

	/**
	 * @since wd.qe
	 *
	 * @var DataValueTable[]
	 */
	private $tables;

	/**
	 * @since wd.qe
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Returns all tables.
	 * Array keys are data value types pointing to the corresponding table.
	 *
	 * @since wd.qe
	 *
	 * @return DataValueTable[]
	 */
	public function getTables() {
		$this->initialize();

		return $this->tables;
	}

	/**
	 * Returns the table for the specified DataValue type.
	 *
	 * @since wd.qe
	 *
	 * @param string $dataValueType
	 *
	 * @return DataValueTable
	 * @throws InvalidArgumentException
	 */
	public function getTable( $dataValueType ) {
		$this->initialize();

		if ( !array_key_exists( $dataValueType, $this->tables ) ) {
			throw new InvalidArgumentException( "There is no table registered for DataValue type '$dataValueType'" );
		}

		return $this->tables[$dataValueType];
	}

	/**
	 * Initialize the object if not done so already.
	 *
	 * @since wd.qe
	 */
	private function initialize() {
		if ( $this->initialized ) {
			return;
		}

		$this->tables = $this->getDefaultTables();

		// TODO: hook

		$this->initialized = true;
	}

	/**
	 * @since wd.qe
	 *
	 * @return DataValueTable[]
	 */
	private function getDefaultTables() {
		$tables = array();

		$tables['boolean'] = new DataValueTable(
			new TableDefinition(
				'boolean',
				array(
					new FieldDefinition( 'value', FD::TYPE_BOOLEAN, false ),
				)
			),
			'value',
			'value'
		);

		$tables['string'] = new DataValueTable(
			new TableDefinition(
				'string',
				array(
					new FieldDefinition( 'value', FD::TYPE_TEXT, false ),
				)
			),
			'value',
			'value',
			'value'
		);

		$tables['monolingualtext'] = new DataValueTable(
			new TableDefinition(
				'mono_text',
				array(
					new FieldDefinition( 'text', FD::TYPE_TEXT, false ),
					new FieldDefinition( 'language', FD::TYPE_TEXT, false ),
					new FieldDefinition( 'json', FD::TYPE_TEXT, false ),
				)
			),
			'json',
			'text',
			'text'
		);

		$tables['geocoordinate'] = new DataValueTable(
			new TableDefinition(
				'geo',
				array(
					new FieldDefinition( 'lat', FD::TYPE_FLOAT, false ),
					new FieldDefinition( 'lon', FD::TYPE_FLOAT, false ),
					new FieldDefinition( 'alt', FD::TYPE_FLOAT, true ),
					new FieldDefinition( 'globe', FD::TYPE_TEXT, true ),
					new FieldDefinition( 'json', FD::TYPE_TEXT, false ),
				)
			),
			'json',
			'lat'
		);

		$tables['number'] = new DataValueTable(
			new TableDefinition(
				'number',
				array(
					new FieldDefinition( 'value', FD::TYPE_FLOAT, false ),
					new FieldDefinition( 'json', FD::TYPE_TEXT, false ),
				)
			),
			'json',
			'value',
			'value'
		);

		$tables['iri'] = new DataValueTable(
			new TableDefinition(
				'iri',
				array(
					new FieldDefinition( 'scheme', FD::TYPE_TEXT, FD::NOT_NULL ),
					new FieldDefinition( 'fragment', FD::TYPE_TEXT, FD::NOT_NULL ),
					new FieldDefinition( 'query', FD::TYPE_TEXT, FD::NOT_NULL ),
					new FieldDefinition( 'hierp', FD::TYPE_TEXT, FD::NOT_NULL ),

					new FieldDefinition( 'iri', FD::TYPE_TEXT, FD::NOT_NULL ),
					new FieldDefinition( 'json', FD::TYPE_TEXT, FD::NOT_NULL ),
				)
			),
			'json',
			'iri',
			'iri'
		);

		$tables['wikibase-entityid'] = new DataValueTable(
			new TableDefinition(
				'entityid',
				array(
					new FieldDefinition( 'type', FD::TYPE_TEXT, false ),
					new FieldDefinition( 'number', FD::TYPE_INTEGER, false ),
					new FieldDefinition( 'json', FD::TYPE_TEXT, false ),
				)
			),
			'json',
			'number'
		);

		return $tables;
	}

}
