<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\TableDefinition;
use DataValues\DataValue;

/**
 * Represents the mapping between a DataValue type and the
 * associated implementation in the store.
 *
 * Based on SMWDataItemHandler by Nischay Nahata and Markus Krötzsch.
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
abstract class DataValueHandler {

	/**
	 * Returns the definition of a table to hold DataValue objects of
	 * the type handled by this DataValueHandler.
	 *
	 * @since wd.qe
	 *
	 * @return TableDefinition
	 */
	abstract public function getTableDefinition();

	/**
	 * Returns the name of the field that holds the value from which
	 * a DataValue instance can be (re)constructed.
	 *
	 * The field should clearly be part of the table returned
	 * by @see getTableDefinition.
	 *
	 * @since wd.qe
	 *
	 * @return string
	 */
	abstract public function getValueFieldName();

	/**
	 * Return the field used to select this type of DataValue. In
	 * particular, this identifies the column that is used to sort values
	 * of this kind. Every type of data returns a non-empty string here.
	 *
	 * @since wd.qe
	 *
	 * @return string
	 */
	abstract public function getSortField();

	/**
	 * Create a DataValue from a cell value in the tables value field.
	 *
	 * @since wd.qe
	 *
	 * @param $dbValue // TODO: mixed or string?
	 *
	 * @return DataValue
	 */
	abstract public function newDataValueFromDbValue( $dbValue );

		/**
	 * Return the label field for this type of DataValue. This should be
	 * a string column in the database table that can be used for selecting
	 * values using criteria such as "starts with". The return value can be
	 * empty if this is not supported. This is preferred for SMWDataItem
	 * classes that do not have an obvious canonical string writing anyway.
	 *
	 * The return value can be a column name or the empty string (if the
	 * give type of DataValue does not have a label field).
	 *
	 * @since wd.qe
	 *
	 * @return string|null
	 */
	public function getLabelField() {
		return null;
	}

	// TODO: getInsertValues and getWhereConds

}