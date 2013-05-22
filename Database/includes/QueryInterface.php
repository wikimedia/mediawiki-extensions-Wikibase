<?php

namespace Wikibase\Database;

/**
 * Interface for objects that provide a database query service.
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
 * @ingroup WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface QueryInterface {

	/**
	 * Returns if the table exists in the database.
	 *
	 * @since 0.1
	 *
	 * @param string $tableName
	 *
	 * @return boolean
	 */
	public function tableExists( $tableName );

	/**
	 * Creates a table based on the provided definition in the store.
	 *
	 * @since 0.1
	 *
	 * @param TableDefinition $table
	 *
	 * @return boolean Success indicator
	 */
	public function createTable( TableDefinition $table );

	/**
	 * Removes the table with provided name from the store.
	 *
	 * @since 0.1
	 *
	 * @param string $tableName
	 *
	 * @return boolean Success indicator
	 */
	public function dropTable( $tableName );

	/**
	 * Inserts the provided values into the specified table.
	 * The values are provided as an associative array in
	 * which the keys are the field names.
	 *
	 * @since 0.1
	 *
	 * @param string $tableName
	 * @param array $values
	 *
	 * @return boolean Success indicator
	 * TODO: change to exception
	 */
	public function insert( $tableName, array $values );

	/**
	 * Updates the rows that match the conditions with the provided values.
	 * The values and conditions are provided as an associative array in
	 * which the keys are the field names.
	 *
	 * @since 0.1
	 *
	 * @param string $tableName
	 * @param array $values
	 * @param array $conditions
	 *
	 * @throws UpdateFailedException
	 */
	public function update( $tableName, array $values, array $conditions );

	/**
	 * Removes the rows matching the provided conditions from the specified table.
	 * The conditions are provided as an associative array in
	 * which the keys are the field names.
	 *
	 * @since 0.1
	 *
	 * @param string $tableName
	 * @param array $conditions
	 *
	 * @throw DeleteFailedException
	 */
	public function delete( $tableName, array $conditions );

	/**
	 * The ID generated for an AUTO_INCREMENT column by the previous
	 * query on success, 0 if the previous
	 * query does not generate an AUTO_INCREMENT value.
	 *
	 * @since 0.1
	 *
	 * @return int
	 */
	public function getInsertId();

	/**
	 * Selects the specified fields from the rows that match the provided conditions.
	 * The conditions are provided as an associative array in
	 * which the keys are the field names.
	 *
	 * @since 0.1
	 *
	 * @param string $tableName
	 * @param array $fields
	 * @param array $conditions
	 *
	 * @return ResultIterator
	 */
	public function select( $tableName, array $fields, array $conditions );

}
