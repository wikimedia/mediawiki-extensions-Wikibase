<?php

namespace Wikibase\Store\Sql;

use Database;
use RedisLockManager;

/**
 * Redis based implementation of ChangeDispatchCoordinator;
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class RedisLockSqlChangeDispatchCoordinator extends SqlChangeDispatchCoordinator {

	/**
	 * @var RedisLockManager
	 */
	private $lockManager;

	/**
	 * RedisLockSqlChangeDispatchCoordinator constructor.
	 *
	 * @param RedisLockManager $lockManager
	 * @param string|false $repoDB
	 * @param string $repoSiteId The repo's global wiki ID
	 */
	public function __construct( RedisLockManager $lockManager, $repoDB, $repoSiteId ) {
		$this->lockManager = $lockManager;
		parent::__construct( $repoDB, $repoSiteId );
	}

	/**
	 * @see SqlChangeDispatchCoordinator::engageClientLock()
	 *
	 * @param Database $db The database connection to work on.
	 * @param string $lock The name of the lock to engage.
	 *
	 * @return bool whether the lock was engaged successfully.
	 */
	private function engageClientLock( Database $db, $lock ) {

		return $this->lockManager->lock( [ $lock ] )->isOK();
	}

	/**
	 * @see SqlChangeDispatchCoordinator::releaseClient()
	 *
	 * @param Database $db The database connection to work on.
	 * @param string $lock The name of the lock to release.
	 *
	 * @return bool whether the lock was released successfully.
	 */
	private function releaseClientLock( Database $db, $lock ) {

		return $this->lockManager->unlock( [ $lock ] )->isOK();
	}

	/**
	 * @see SqlChangeDispatchCoordinator::isClientLockUsed()
	 *
	 * @param Database $db The database connection to work on.
	 * @param string $lock The name of the lock to check.
	 *
	 * @return bool true if the given lock is currently held by another process, false otherwise.
	 */
	private function isClientLockUsed( Database $db, $lock ) {

		// Not needed in redis locks
		return false;
	}

	/**
	 * @return RedisLockManager
	 */
	public function getLockManager() {
		return $this->lockManager;
	}

	/**
	 * @param RedisLockManager $lockManager
	 */
	public function setLockManager( RedisLockManager $lockManager ) {
		$this->lockManager = $lockManager;
	}

}
