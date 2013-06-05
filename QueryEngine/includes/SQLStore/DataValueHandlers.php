<?php

namespace Wikibase\QueryEngine\SQLStore;

use OutOfBoundsException;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\FieldDefinition as FD;
use Wikibase\Database\TableDefinition;
use Wikibase\QueryEngine\SQLStore\DVHandler\BooleanHandler;
use Wikibase\QueryEngine\SQLStore\DVHandler\EntityIdHandler;
use Wikibase\QueryEngine\SQLStore\DVHandler\GeoCoordinateHandler;
use Wikibase\QueryEngine\SQLStore\DVHandler\IriHandler;
use Wikibase\QueryEngine\SQLStore\DVHandler\MonolingualTextHandler;
use Wikibase\QueryEngine\SQLStore\DVHandler\NumberHandler;
use Wikibase\QueryEngine\SQLStore\DVHandler\StringHandler;
use Wikibase\QueryEngine\SQLStore\DataValueTable;

/**
 * A collection of DataValueHandler objects to be used by the store.
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
final class DataValueHandlers {

	/**
	 * @since 0.1
	 *
	 * @var DataValueHandler[]
	 */
	private $dvHandlers;

	/**
	 * @since 0.1
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Returns all DataValueHandler objects.
	 * Array keys are data value types pointing to the corresponding DataValueHandler.
	 *
	 * @since 0.1
	 *
	 * @return DataValueHandler[]
	 */
	public function getHandlers() {
		$this->initialize();

		return $this->dvHandlers;
	}

	/**
	 * Returns the DataValueHandler for the specified DataValue type.
	 * Note that this is not suited for interaction with the actual store schema,
	 * for that one should use the Schema class.
	 *
	 * @since 0.1
	 *
	 * @param string $dataValueType
	 *
	 * @return DataValueHandler
	 * @throws OutOfBoundsException
	 */
	public function getHandler( $dataValueType ) {
		$this->initialize();

		if ( !array_key_exists( $dataValueType, $this->dvHandlers ) ) {
			throw new OutOfBoundsException( "There is no DataValueHandler registered for DataValue type '$dataValueType'" );
		}

		return $this->dvHandlers[$dataValueType];
	}

	/**
	 * Initialize the object if not done so already.
	 *
	 * @since 0.1
	 */
	private function initialize() {
		if ( $this->initialized ) {
			return;
		}

		$this->dvHandlers = $this->getDefaultHandlers();

		// TODO: hook

		$this->initialized = true;
	}

	/**
	 * @since 0.1
	 *
	 * @return DataValueTable[]
	 */
	private function getDefaultHandlers() {
		$tables = array();

		$tables['boolean'] = new BooleanHandler( new DataValueTable(
			new TableDefinition(
				'boolean',
				array(
					new FieldDefinition( 'value', FD::TYPE_BOOLEAN, false ),
				)
			),
			'value',
			'value'
		) );

		$tables['string'] = new StringHandler( new DataValueTable(
			new TableDefinition(
				'string',
				array(
					new FieldDefinition( 'value', FD::TYPE_TEXT, false ),
				)
			),
			'value',
			'value',
			'value'
		) );

		$tables['monolingualtext'] = new MonolingualTextHandler( new DataValueTable(
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
		) );

		$tables['globecoordinate'] = new GeoCoordinateHandler( new DataValueTable(
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
		) );

		$tables['number'] = new NumberHandler( new DataValueTable(
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
		) );

		$tables['iri'] = new IriHandler( new DataValueTable(
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
		) );

		// TODO: register via hook
		$tables['wikibase-entityid'] = new EntityIdHandler( new DataValueTable(
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
		) );

		return $tables;
	}

}
