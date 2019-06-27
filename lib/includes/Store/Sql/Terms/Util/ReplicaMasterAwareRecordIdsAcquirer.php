<?php

namespace Wikibase\Lib\Store\Sql\Terms\Util;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILBFactory;
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
	 * This flag changes this object's behavior so that it always queries
	 * master database to find existing items, bypassing replica database
	 * completely.
	 */
	const FLAG_IGNORE_REPLICA = 0x1;

	/**
	 * @var ILBFactory
	 */
	private $lbFactory;

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
	 * @var int
	 */
	private $flags;

	/**
	 * @param ILBFactory $lbFactory
	 * @param string $table the name of the table this acquirer is for
	 * @param string $idColumn the name of the column that contains the desired ids
	 * @param LoggerInterface|null $logger
	 * @param int $flags {@see self::FLAG_IGNORE_REPLICA}
	 * @param int $waitForReplicationTimeout in seconds, the timeout on waiting for replication
	 */
	public function __construct(
		ILBFactory $lbFactory,
		$table,
		$idColumn,
		LoggerInterface $logger = null,
		$flags = 0x0,
		$waitForReplicationTimeout = 2
	) {
		$this->lbFactory = $lbFactory;
		$this->table = $table;
		$this->idColumn = $idColumn;
		$this->logger = $logger ?? new NullLogger();
		$this->flags = $flags;
		$this->waitForReplicationTimeout = $waitForReplicationTimeout;
	}

	private function getLoadBalancer(): ILoadBalancer {
		return $this->lbFactory->getMainLB();
	}

	/**
	 * Acquire ids of needed records in the table, inserting non-existing
	 * ones into master database.
	 *
	 * Note 1: this function assumes that all records given in $neededRecords specify
	 * the same columns. If some records specify less, more or different columns than
	 * the first one does, the behavior is not defined. The first element keys will be
	 * used as the set of columns to select in database and to provide back in the returned array.
	 *
	 * Note 2: this function assumes that all records given in $neededRecords have
	 * their values as strings. If some values are of different type (e.g. integer ids)
	 * this can cause a false mismatch in identifying records selected in
	 * database with their corresponding needed records.
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
	 *	the array of records that are about to be inserted into master database, and should
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
	) {
		if ( $this->isIgnoringReplica() ) {
			$existingRecords = $this->fetchExistingRecordsFromMaster( $neededRecords );
		} else {
			$existingRecords = $this->fetchExistingRecordsFromReplica( $neededRecords );
		}

		$neededRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );

		if ( !empty( $neededRecords ) ) {
			if ( is_callable( $recordsToInsertDecoratorCallback ) ) {
				$neededRecords = $recordsToInsertDecoratorCallback( $neededRecords );
			}

			$this->insertNonExistingRecordsIntoMaster( array_unique( $neededRecords, SORT_REGULAR ) );

			$existingRecords = array_merge(
				$existingRecords,
				$this->fetchExistingRecordsFromMaster( $neededRecords )
			);
		}

		return $existingRecords;
	}

	private function fetchExistingRecordsFromMaster( array $neededRecords ): array {
		return $this->findExistingRecords( $this->getDbMaster(), $neededRecords );
	}

	private function fetchExistingRecordsFromReplica( array $neededRecords ): array {
		// Fetching existing records from replica
		$existingRecords = $this->findExistingRecords( $this->getDbReplica(), $neededRecords );
		$neededRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );

		// If not all needed records exist in replica,
		// try wait for replication and fetch again from replica
		if ( !empty( $neededRecords ) ) {
			$this->lbFactory->waitForReplication( [
				'timeout' => $this->waitForReplicationTimeout
			] );

			$existingRecords = array_merge(
				$existingRecords,
				$this->findExistingRecords( $this->getDbReplica(), $neededRecords )
			);

			$neededRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );
		}

		return $existingRecords;
	}

	private function getDbReplica() {
		if ( $this->dbReplica === null ) {
			$this->dbReplica = $this->getLoadBalancer()->getConnection( ILoadBalancer::DB_REPLICA );
		}

		return $this->dbReplica;
	}

	private function getDbMaster() {
		if ( $this->dbMaster === null ) {
			$this->dbMaster = $this->getLoadBalancer()->getConnection( ILoadBalancer::DB_MASTER );
		}

		return $this->dbMaster;
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

	/**
	 * @param array $neededRecords
	 * @suppress SecurityCheck-SQLInjection
	 */
	private function insertNonExistingRecordsIntoMaster( array $neededRecords ) {
		$this->getDbMaster()->insert( $this->table, $neededRecords );
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
			unset( $record[$this->idColumn] );
			$recordHash = $this->calcRecordHash( $record );

			if ( !isset( $existingRecordsHashes[$recordHash] ) ) {
				$nonExistingRecords[] = $record;
			}
		}

		return $nonExistingRecords;
	}

	private function calcRecordHash( array $record ) {
		ksort( $record );
		return md5( serialize( $record ) );
	}

	private function isIgnoringReplica() {
		return ( $this->flags & self::FLAG_IGNORE_REPLICA ) !== 0x0;
	}

}
