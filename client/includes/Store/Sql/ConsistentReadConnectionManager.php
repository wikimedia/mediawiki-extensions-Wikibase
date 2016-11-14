<?php

namespace Wikibase\Client\Store\Sql;

use DatabaseBase;
use IDatabase;
use InvalidArgumentException;
use LoadBalancer;

/**
 * Database connection manager.
 *
 * This manages access to master and slave databases. It also manages state that indicates whether
 * the slave databases are possibly outdated after a write operation, and thus the master database
 * should be used for subsequent read operations.
 *
 * @note: Services that access overlapping sets of database tables, or interact with logically
 * related sets of data in the database, should share a ConsistentReadConnectionManager. Services accessing
 * unrelated sets of information may prefer to not share a ConsistentReadConnectionManager, so they can still
 * perform read operations against slave databases after a (unrelated, per the assumption) write
 * operation to the master database. Generally, sharing a ConsistentReadConnectionManager improves consistency
 * (by avoiding race conditions due to replication lag), but can reduce performance (by directing
 * more read operations to the master database server).
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ConsistentReadConnectionManager {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * The symbolic name of the target database, or false for the local wiki's database.
	 *
	 * @var string|false
	 */
	private $dbName;

	/**
	 * @var bool If true, getReadConnection() will also return a DB_MASTER connection.
	 */
	private $forceMaster = false;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param string|bool $dbName Optional, defaults to current wiki.
	 *        This follows the convention for database names used by $loadBalancer.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( LoadBalancer $loadBalancer, $dbName = false ) {
		if ( !is_string( $dbName ) && $dbName !== false ) {
			throw new InvalidArgumentException( '$dbName must be a string, or false.' );
		}

		$this->loadBalancer = $loadBalancer;
		$this->dbName = $dbName;
	}

	/**
	 * Forces all future calls to getReadConnection() to return a connection to the master DB.
	 * Use this before performing read operations that are critical for a future update.
	 * Calling beginAtomicSection() implies a call to forceMaster().
	 */
	public function forceMaster() {
		$this->forceMaster = true;
	}

	/**
	 * Returns a database connection for reading. The connection should later be released by
	 * calling releaseConnection().
	 *
	 * @note: If forceMaster() or beginAtomicSection() were previously called on this
	 * ConsistentReadConnectionManager instance, this method will return a connection to the master database,
	 * to avoid inconsistencies.
	 *
	 * @return DatabaseBase
	 */
	public function getReadConnection() {
		$dbIndex = $this->forceMaster ? DB_MASTER : DB_SLAVE;
		return $this->loadBalancer->getConnection( $dbIndex, [], $this->dbName );
	}

	/**
	 * Returns a connection to the master DB, for updating. The connection should later be released
	 * by calling releaseConnection().
	 *
	 * @return DatabaseBase
	 */
	public function getWriteConnection() {
		return $this->loadBalancer->getConnection( DB_MASTER, [], $this->dbName );
	}

	/**
	 * @param DatabaseBase $db
	 */
	public function releaseConnection( IDatabase $db ) {
		$this->loadBalancer->reuseConnection( $db );
	}

	/**
	 * Begins an atomic section and returns a database connection to the master DB, for updating.
	 *
	 * @note: This causes all future calls to getReadConnection() to return a connection
	 * to the master DB, even after commitAtomicSection() or rollbackAtomicSection() have
	 * been called.
	 *
	 * @param string $fname
	 *
	 * @return DatabaseBase
	 */
	public function beginAtomicSection( $fname ) {
		// Once we have written to master, do not read from slave.
		$this->forceMaster();

		$db = $this->getWriteConnection();
		$db->startAtomic( $fname );
		return $db;
	}

	/**
	 * @param DatabaseBase $db
	 * @param string $fname
	 */
	public function commitAtomicSection( IDatabase $db, $fname ) {
		$db->endAtomic( $fname );
		$this->releaseConnection( $db );
	}

	/**
	 * @param DatabaseBase $db
	 * @param string $fname
	 */
	public function rollbackAtomicSection( IDatabase $db, $fname ) {
		//FIXME: there does not seem to be a clean way to roll back an atomic section?!
		$db->rollback( $fname, 'flush' );
		$this->releaseConnection( $db );
	}

}
