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
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @return IDatabase
	 */
	public function getReadConnection() {
		return $this->loadBalancer->getConnection( DB_READ );
	}

	/**
	 * @return IDatabase
	 */
	private function getWriteConnection() {
		return $this->loadBalancer->getConnection( DB_WRITE );
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
