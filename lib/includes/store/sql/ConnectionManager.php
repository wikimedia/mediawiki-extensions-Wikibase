<?php

namespace Wikibase\Store\Sql;

use DatabaseBase;
use IDatabase;
use InvalidArgumentException;
use LoadBalancer;

/**
 * Database connection manager.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ConnectionManager {

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
	 * @param string|false $dbName Optional, defaults to current wiki.
	 *        This follows the convention for database names used by $loadBalancer.
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
	 * Returns a database connection for reading.
	 *
	 * @note: If forceMaster() or beginAtomicSection() were previously called on this
	 * ConnectionManager instance, this method will return a connection to the master database,
	 * to avoid inconsistencies.
	 *
	 * @return DatabaseBase
	 */
	public function getReadConnection() {
		$dbIndex = $this->forceMaster ? DB_MASTER : DB_SLAVE;
		return $this->loadBalancer->getConnection( $dbIndex, array(), $this->dbName );
	}

	/**
	 * Returns a connection to the master DB, for updating.
	 *
	 * @return DatabaseBase
	 */
	private function getWriteConnection() {
		return $this->loadBalancer->getConnection( DB_MASTER, array(), $this->dbName );
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
