<?php

namespace Wikibase\Lib\Store\Sql\Terms\Util;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Allows acquiring ids of records in database table,
 * by inspecting a given read-only replica database to initially
 * find existing records with their ids, and insert non-existing
 * records into a read-write master database and getting those
 * ids as well from the master database after insertion.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class ReplicaMasterAwareRecordIdsAcquirer {

	/**
	 * This flag changes this object's behavior so that it always queries
	 * master database to find existing items, bypassing replica database
	 * completely.
	 */
	public const FLAG_IGNORE_REPLICA = 0x1;

	/**
	 * @var RepoDomainDb
	 */
	private $repoDb;

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
	 * @var int
	 */
	private $waitForReplicationTimeout;

	/**
	 * @param RepoDomainDb $repoDb
	 * @param string $table the name of the table this acquirer is for
	 * @param string $idColumn the name of the column that contains the desired ids
	 * @param LoggerInterface|null $logger
	 * @param int $flags {@see self::FLAG_IGNORE_REPLICA}
	 * @param int $waitForReplicationTimeout in seconds, the timeout on waiting for replication
	 */
	public function __construct(
		RepoDomainDb $repoDb,
		$table,
		$idColumn,
		LoggerInterface $logger = null,
		$flags = 0x0,
		$waitForReplicationTimeout = 2
	) {
		$this->repoDb = $repoDb;
		$this->table = $table;
		$this->idColumn = $idColumn;
		$this->logger = $logger ?? new NullLogger();
		$this->flags = $flags;
		$this->waitForReplicationTimeout = $waitForReplicationTimeout;
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
	 * @return array[] the array of input records along with their ids
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
		$existingRecords = $this->fetchExistingRecords( $neededRecords );
		$nonExistingRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );
		$insertedRecords = $this->insertNonExistingRecords(
			$nonExistingRecords, $recordsToInsertDecoratorCallback );

		return array_merge(
			$existingRecords,
			$insertedRecords
		);
	}

	private function fetchExistingRecords( array $neededRecords ): array {
		$existingRecords = [];

		// First fetch from replica, unless we are ignoring it
		if ( !$this->isIgnoringReplica() ) {
			$existingRecords = array_merge(
				$existingRecords,
				$this->fetchExistingRecordsFromReplica( $neededRecords )
			);
			$neededRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );
		}

		// Then fetch from master
		if ( !empty( $neededRecords ) ) {
			$existingRecords = array_merge(
				$existingRecords,
				$this->fetchExistingRecordsFromMaster( $neededRecords )
			);
		}

		return $existingRecords;
	}

	private function insertNonExistingRecords(
		array $records,
		$recordsToInsertDecoratorCallback = null
	): array {
		if ( empty( $records ) ) {
			return [];
		}

		$forLogStartRecords = $records;

		if ( is_callable( $recordsToInsertDecoratorCallback ) ) {
			$records = $recordsToInsertDecoratorCallback( $records );
		}

		$insertedRecords = [];
		while ( !empty( $records ) ) {

			$recordsCount = count( $records );

			$this->insertNonExistingRecordsIntoMaster( $records );

			$insertedRecords = array_merge(
				$insertedRecords,
				$this->fetchExistingRecordsFromMaster( $records )
			);

			$records = $this->filterNonExistingRecords( $records, $insertedRecords );

			if ( count( $records ) === $recordsCount ) {
				// Edge case. When it couldn't find the row but it couldn't insert it either.
				// This can happen when this code tries to read the id and can't find it then another thread inserts data at the
				// same time so this code can't insert it either but then it tries to read it again and given the lock mode of
				// REPEATABLE_READ, which is the default for MySQL, the code end up not being be able to read the id again and
				// gets stuck in an infinite loop. To avoid this, we read it with CONN_TRX_AUTOCOMMIT
				// Surprisingly it's not too rare not to happen in production: T247553

				$dbw = $this->repoDb->connections()->getWriteConnection( ILoadBalancer::CONN_TRX_AUTOCOMMIT );

				$insertedRecords = array_merge(
					$insertedRecords,
					$this->fetchExistingRecordsFromMaster( $records, $dbw )
				);

				$records = $this->filterNonExistingRecords( $records, $insertedRecords );

				if ( count( $records ) === $recordsCount ) {
					wfDebugLog(
						'Wikibase',
						__METHOD__ . ': Fail-safe: ' .
						' $recordsCount: ' . json_encode( $recordsCount ) .
						' $forLogStartRecords: ' . json_encode( $forLogStartRecords ) .
						' $insertedRecords: ' . json_encode( $insertedRecords ) .
						' $records: ' . json_encode( $records )
					);
					// Logic error, this should never happen.
					$exception = new Exception(
						'Fail-safe exception. Avoiding infinite loop due to possibly undetectable'
						. " existing records in master.\n"
						. ' It may be due to encoding incompatibility'
						. ' between database values and values passed in $neededRecords parameter.'
					);
					throw $exception;
				}
			}
		}

		return $insertedRecords;
	}

	private function fetchExistingRecordsFromMaster( array $neededRecords, IDatabase $dbw = null ): array {
		$dbw = $dbw ?? $this->getDbMaster();
		return $this->findExistingRecords( $dbw, $neededRecords );
	}

	private function fetchExistingRecordsFromReplica( array $neededRecords ): array {
		$dbr = $this->getDbReplica();

		// Fetching existing records from replica
		$existingRecords = $this->findExistingRecords( $dbr, $neededRecords );
		$neededRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );

		// If not all needed records exist in replica,
		// try wait for replication and fetch again from replica
		if ( !empty( $neededRecords ) ) {
			$this->repoDb->replication()->waitForAllAffectedClusters( $this->waitForReplicationTimeout );

			$existingRecords = array_merge(
				$existingRecords,
				$this->findExistingRecords( $dbr, $neededRecords )
			);

			$neededRecords = $this->filterNonExistingRecords( $neededRecords, $existingRecords );
		}

		return $existingRecords;
	}

	private function getDbReplica() {
		return $this->repoDb->connections()->getReadConnection();
	}

	private function getDbMaster() {
		return $this->repoDb->connections()->getWriteConnection();
	}

	/**
	 * @param IDatabase $db Caller can choose for this to be the Master or Replica
	 * @param array $neededRecords
	 * @return array
	 */
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
		$existingRows = $db->newSelectQueryBuilder()
			->select( array_keys( $neededRecords[0] ) )
			->select( $this->idColumn )
			->from( $this->table )
			->where( $db->makeList( $recordsSelectConditions, IDatabase::LIST_OR ) )
			->caller( __METHOD__ )
			->fetchResultSet();

		$existingRecords = [];
		foreach ( $existingRows as $row ) {
			$existingRecords[] = (array)$row;
		}

		return $existingRecords;
	}

	/**
	 * @param array $neededRecords
	 * @suppress SecurityCheck-SQLInjection
	 */
	private function insertNonExistingRecordsIntoMaster( array $neededRecords ) {
		$this->getDbMaster()->insert( $this->table, $neededRecords, __METHOD__, [ 'IGNORE' ] );
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
				$nonExistingRecords[$recordHash] = $record;
			}
		}

		return array_values( $nonExistingRecords );
	}

	private function calcRecordHash( array $record ) {
		ksort( $record );
		return md5( serialize( $record ) );
	}

	private function isIgnoringReplica() {
		return ( $this->flags & self::FLAG_IGNORE_REPLICA ) !== 0x0;
	}

}
