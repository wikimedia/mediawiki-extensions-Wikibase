<?php

namespace Wikibase\Client\Store\Sql;

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
	 * @return IDatabase
	 */
	public function getReadConnection() {
		if ( $this->dbName === null ) {
			return $this->loadBalancer->getConnection( DB_READ );
		}

		return $this->loadBalancer->getConnection( DB_READ, array(), $this->dbName );
	}

	/**
	 * @return IDatabase
	 */
	private function getWriteConnection() {
		if ( $this->dbName === null ) {
			return $this->loadBalancer->getConnection( DB_WRITE );
		}

		return $this->loadBalancer->getConnection( DB_WRITE, array(), $this->dbName );
	}

	/**
	 * @param IDatabase $db
	 */
	public function releaseConnection( IDatabase $db ) {
		$this->loadBalancer->reuseConnection( $db );
	}

	/**
	 * @param string $fname
	 *
	 * @return IDatabase
	 */
	public function beginAtomicSection( $fname ) {
		$db = $this->getWriteConnection();
		$db->startAtomic( $fname );
		return $db;
	}

	/**
	 * @param IDatabase $db
	 * @param string $fname
	 *
	 * @return IDatabase
	 */
	public function commitAtomicSection( IDatabase $db, $fname ) {
		$db->endAtomic( $fname );
		$this->releaseConnection( $db );
	}

	/**
	 * @param IDatabase $db
	 * @param string $fname
	 *
	 * @return IDatabase
	 */
	public function rollbackAtomicSection( IDatabase $db, $fname ) {
		//FIXME: there does not seem to be a clean way to roll back an atomic section?!
		$db->rollback( $fname, 'flush' );
		$this->releaseConnection( $db );
	}

}
