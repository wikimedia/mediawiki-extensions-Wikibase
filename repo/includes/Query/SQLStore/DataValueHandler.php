<?php

namespace Wikibase\Repo\Query\SQLStore;

use Wikibase\Repo\Database\TableDefinition;
use DataValues\DataValue;

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
	abstract public function getSortFieldName();

	/**
	 * Create a DataValue from a cell value in the tables value field.
	 *
	 * @since wd.qe
	 *
	 * @param $valueFieldValue // TODO: mixed or string?
	 *
	 * @return DataValue
	 */
	abstract public function newDataValueFromValueField( $valueFieldValue );

	/**
	 * Return the label field for this type of DataValue. This should be
	 * a string column in the database table that can be used for selecting
	 * values using criteria such as "starts with". The return value can be
	 * empty if this is not supported. This is preferred for DataValue
	 * classes that do not have an obvious canonical string writing anyway.
	 *
	 * The return value can be a column name or the empty string (if the
	 * give type of DataValue does not have a label field).
	 *
	 * @since wd.qe
	 *
	 * @return string|null
	 */
	public function getLabelFieldName() {
		return null;
	}

	/**
	 * Return an array of fields=>values to conditions (WHERE part) in SQL
	 * queries for the given DataValue. This method can return fewer
	 * fields than getInsertValues as long as they are enough to identify
	 * an item for search.
	 *
	 * The passed DataValue needs to be of a type supported by the DataValueHandler.
	 * If it is not supported, an InvalidArgumentException might be thrown.
	 *
	 * @since wd.qe
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
	 * @since wd.qe
	 *
	 * @param DataValue $value
	 *
	 * @return array
	 */
	abstract public function getInsertValues( DataValue $value );

}
