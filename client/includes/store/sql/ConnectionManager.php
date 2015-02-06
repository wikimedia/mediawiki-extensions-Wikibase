<?php

namespace Wikibase\Client\Store\Sql;

use DatabaseBase;
use IDatabase;
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
	 * @var string|null
	 */
	private $dbName;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param string|null $dbName Optional, defaults to current wiki.
	 */
	public function __construct( LoadBalancer $loadBalancer, $dbName = null ) {
		$this->loadBalancer = $loadBalancer;
		$this->dbName = $dbName;
	}

	/**
	 * @return DatabaseBase
	 */
	public function getReadConnection() {
		if ( $this->dbName === null ) {
			return $this->loadBalancer->getConnection( DB_READ );
		}

		return $this->loadBalancer->getConnection( DB_READ, array(), $this->dbName );
	}

	/**
	 * @return DatabaseBase
	 */
	private function getWriteConnection() {
		if ( $this->dbName === null ) {
			return $this->loadBalancer->getConnection( DB_WRITE );
		}

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
