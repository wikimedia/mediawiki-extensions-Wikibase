<?php

namespace Wikibase\Lib\Store\Sql\Terms\Util;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Allows acquiring ids of records in database table,
 * by inspecting a given read-only replica database to initially
 * find existing records with their ids, and insert non-existing
 * records into a read-write master databas and getting those
 * ids as well from the master database after insertion.
 *
 * @license GPL-2.0-or-later
 */
class ReplicaMasterAwareRecordIdsAcquirer {

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var IDatabase master database to insert non-existing records into
	 */
	private $dbMaster = null;

	/**
	 * @var IDatabase replica database to initially query existing records in
	 */
	private $dbReplica = null;

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var string
	 */
	private $idColumn;

	/**
	 * @var LoggerInterface|null
	 */
	private $logger;

	/**
	 * @param ILoadBalancer $loadBalancer database connection accessor
	 * @param string $table the name of the table this acquirer is for
	 * @param string $idColumn the name of the column that contains the desired ids
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		$table,
		$idColumn,
		LoggerInterface $logger = null
	) {
		$this->loadBalancer = $loadBalancer;
		$this->table = $table;
		$this->idColumn = $idColumn;
		$this->logger = $logger ?? new NullLogger();
	}

	/**
	 * Acquire ids of needed records in the table, inserting non-existing
	 * ones into master database.
	 *
	 * Note 1: this function assumes that all records given in $neededRecords specify
	 * the same columns. If some records specify less, more or different columns than
	 * the first one does, the behavior is not defined.
	 *
	 * Note 2: this function assumes that all records given in $neededRecords have
	 * their values as strings. If some values are of different type (e.g. integer ids)
	 * this can cause infinite loops due to mismatch in identifying records selected in
	 * database with their corresponding needed records. The first element keys will be
	 * used as the set of columns to select in database and to provide back in the returned array.
	 *
	 * @param array $neededRecords array of records to be looked-up or inserted.
	 *	Each entry in this array should an associative array of column => value pairs.
	 *	Example:
	 *	[
	 *		[ 'columnA' => 'valueA1', 'columnB' => 'valueB1' ],
	 *		[ 'columnA' => 'valueA2', 'columnB' => 'valueB2' ],
	 *		...
	 *	]
	 *
	 * @return array the array of input recrods along with their ids
	 *	Example:
	 *	[
	 *		[ 'columnA' => 'valueA1', 'columnB' => 'valueB1', 'idColumn' => '1' ],
	 *		[ 'columnA' => 'valueA2', 'columnB' => 'valueB2', 'idColumn' => '2' ],
	 *		...
	 *	]
	 */
	public function acquireIds( array $neededRecords ) {
		$this->connectReplica();
		$existingRecords = $this->findExistingRecords( $this->dbReplica, $neededRecords );
		$neededRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );

		while ( !empty( $neededRecords ) ) {
			$this->connectMaster();
			$this->insertNonExistingRecordsIntoMaster( $neededRecords );
			$existingRecords = array_merge(
				$existingRecords,
				$this->findExistingRecords( $this->dbMaster, $neededRecords )
			);
			$neededRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );
		}

		return $existingRecords;
	}

	private function connectReplica() {
		if ( $this->dbReplica === null ) {
			$this->dbReplica = $this->loadBalancer->getConnection( ILoadBalancer::DB_REPLICA );
		}
	}

	private function connectMaster() {
		if ( $this->dbMaster === null ) {
			$this->dbMaster = $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		}
	}

	private function findExistingRecords( IDatabase $db, array $neededRecords ): array {
		$recordsSelectConditions = array_map( function ( $record ) use ( $db ) {
			return $db->makeList( $record, IDatabase::LIST_AND );
		}, $neededRecords );

		/*
		 * Todo, related to Note 1 on self::acquireIds():
		 * this class can allow for specifying a different set of columns to select
		 * and return back from self::acquireIds(). This set of columns can be added as
		 * an optional argument to self::acquireIds() for instance, the current solution
		 * in here can be a fallback when that isn't given.
		 */
		$selectColumns = array_keys( $neededRecords[0] );
		$selectColumns[] = $this->idColumn;

		$existingRows = $db->select(
			$this->table,
			$selectColumns,
			$db->makeList( $recordsSelectConditions, IDatabase::LIST_OR )
		);

		$existingRecords = [];
		foreach ( $existingRows as $row ) {
			$existingRecord = [];
			foreach ( $selectColumns as $column ) {
				$existingRecord[$column] = $row->$column;
			}
			$existingRecords[] = $existingRecord;
		}

		return $existingRecords;
	}

	private function insertNonExistingRecordsIntoMaster( array $neededRecords ) {
		$uniqueRecords = [];
		foreach ( $neededRecords as $record ) {
			$recordHash = $this->calcRecordHash( $record );
			$uniqueRecords[$recordHash] = $record;
		}

		try {
			$this->dbMaster->insert( $this->table, array_values( $uniqueRecords ) );
		} catch ( DBQueryError $dbError ) {
			$this->logger->info(
				'{method}: Inserting records into {table} failed: {exception}',
				[
					'method' => __METHOD__,
					'exception' => $dbError,
					'table' => $this->table,
					'records' => $uniqueRecords
				]
			);
		}
	}

	private function filterNonExistingRecords( $neededRecords, $existingRecords ): array {
		$existingRecordsHashes = [];
		foreach ( $existingRecords as $record ) {
			unset( $record[$this->idColumn] );
			$recordHash = $this->calcRecordHash( $record );
			$existingRecordsHashes[$recordHash] = true;
		}

		$nonExistingRecords = [];
		foreach ( $neededRecords as $record ) {
			$recordHash = $this->calcRecordHash( $record );

			if ( !isset( $existingRecordsHashes[$recordHash] ) ) {
				$nonExistingRecords[] = $record;
			}
		}

		return $nonExistingRecords;
	}

	/**
	 * Implementation detail, related to Note 2 on self::acquireIds():
	 * this function relies on the fact that the given set of needed records will have
	 * all values as strings in order to produce hashes that match up correctly with
	 * selected records in database, because database selection will always return
	 * values as strings.
	 */
	private function calcRecordHash( array $record ) {
		ksort( $record );
		return md5( serialize( $record ) );
	}

}
