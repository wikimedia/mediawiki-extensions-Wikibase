<?php

namespace Wikibase\QueryEngine\SQLStore;

use DataValues\DataValue;
use Wikibase\Database\TableDefinition;

/**
 * Represents the mapping between a DataValue type and the
 * associated implementation in the store.
 *
 * Based on, and containing snippets from, SMWDataItemHandler from Semantic MediaWiki.
 * SMWDataItemHandler was written by Nischay Nahata and Markus Krötzsch.
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
abstract class DataValueHandler implements \Immutable {

	/**
	 * @since 0.1
	 *
	 * @var DataValueTable
	 */
	protected $dataValueTable;

	/**
	 * @since 0.1
	 *
	 * @param DataValueTable $dataValueTable
	 */
	public function __construct( DataValueTable $dataValueTable ) {
		$this->dataValueTable = $dataValueTable;
	}

	/**
	 * @since 0.1
	 *
	 * @return DataValueTable
	 */
	public function getDataValueTable() {
		return $this->dataValueTable;
	}

	/**
	 * Create a DataValue from a cell value in the tables value field.
	 *
	 * @since 0.1
	 *
	 * @param $valueFieldValue // TODO: mixed or string?
	 *
	 * @return DataValue
	 */
	abstract public function newDataValueFromValueField( $valueFieldValue );

	/**
	 * Return an array of fields=>values to conditions (WHERE part) in SQL
	 * queries for the given DataValue. This method can return fewer
	 * fields than getInsertValues as long as they are enough to identify
	 * an item for search.
	 *
	 * The passed DataValue needs to be of a type supported by the DataValueHandler.
	 * If it is not supported, an InvalidArgumentException might be thrown.
	 *
	 * @since 0.1
	 *
	 * @param DataValue $value
	 *
	 * @return array
	 */
	abstract public function getWhereConditions( DataValue $value );

	/**
	 * Return an array of fields=>values that is to be inserted when
	 * writing the given DataValue to the database. Values should be set
	 * for all columns, even if NULL. This array is used to perform all
	 * insert operations into the DB.
	 *
	 * The passed DataValue needs to be of a type supported by the DataValueHandler.
	 * If it is not supported, an InvalidArgumentException might be thrown.
	 *
	 * @since 0.1
	 *
	 * @param DataValue $value
	 *
	 * @return array
	 */
	abstract public function getInsertValues( DataValue $value );

	/**
	 * Returns a clone of the DataValueHandler, though with the provided DataValue table rather then the original one.
	 *
	 * @since wd.db
	 *
	 * @param DataValueTable $dataValueTable
	 *
	 * @return DataValueHandler
	 */
	public function mutateDataValueTable( DataValueTable $dataValueTable ) {
		return new static( $dataValueTable );
	}

}
