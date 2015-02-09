<?php

namespace Wikibase\Client\Store\Sql;

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
	 * @return DatabaseBase
	 */
	public function getReadConnection() {
		return $this->loadBalancer->getConnection( DB_READ, array(), $this->dbName );
	}

	/**
	 * @return DatabaseBase
	 */
	private function getWriteConnection() {
		return $this->loadBalancer->getConnection( DB_WRITE, array(), $this->dbName );
	}

	/**
	 * @param DatabaseBase $db
	 */
	public function releaseConnection( IDatabase $db ) {
		$this->loadBalancer->reuseConnection( $db );
	}

	/**
	 * @param string $fname
	 *
	 * @return DatabaseBase
	 */
	public function beginAtomicSection( $fname ) {
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
