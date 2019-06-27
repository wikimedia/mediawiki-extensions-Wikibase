<?php

namespace Wikibase\Lib\Store\Sql;

/**
 * Allows acquiring ids of records in database table,
 * by searching for existing records with their ids first,
 * then inserting non-existing records and getting their ids.
 *
 * @license GPL-2.0-or-later
 */
interface RecordIdsAcquirer {

	/**
	 * Acquire ids of needed records in the table, inserting non-existing ones.
	 *
	 * @param array $neededRecords array of records to be looked-up or inserted.
	 *	Each entry in this array should an associative array of column => value pairs.
	 *	Example:
	 *	[
	 *		[ 'columnA' => 'valueA1', 'columnB' => 'valueB1' ],
	 *		[ 'columnA' => 'valueA2', 'columnB' => 'valueB2' ],
	 *		...
	 *	]
	 * @param callable|null $recordsToInsertDecoratorCallback a callback that will be passed
	 *	the array of records that are about to be inserted, and should
	 *	return a new array of records to insert, allowing to enhance and/or supply more default
	 *	values for other columns that are not supplied as part of $neededRecords array.
	 *
	 * @return array the array of input recrods along with their ids
	 *	Example:
	 *	[
	 *		[ 'columnA' => 'valueA1', 'columnB' => 'valueB1', 'idColumn' => '1' ],
	 *		[ 'columnA' => 'valueA2', 'columnB' => 'valueB2', 'idColumn' => '2' ],
	 *		...
	 *	]
	 */
	public function acquireIds(
		array $neededRecords,
		$recordsToInsertDecoratorCallback = null
	);
}
