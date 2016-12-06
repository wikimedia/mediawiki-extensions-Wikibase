<?php

namespace Wikibase\Repo\Store\Sql;

use Database;
use LockManager;
use Wikibase\Store\Sql\SqlChangeDispatchCoordinator;

/**
 * SQL-based implementation of ChangeDispatchCoordinator when there is a
 * LockManager implementation provided to be used instead of
 * SqlChangeDisptachCoordinator's own locking.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LockManagerSqlChangeDispatchCoordinator extends SqlChangeDispatchCoordinator {

	/**
	 * @var LockManager
	 */
	private $lockManager;

	/**
	 * @param LockManager $lockManager
	 * @param string|false $repoDB
	 * @param string $repoSiteId The repo's global wiki ID
	 */
	public function __construct( LockManager $lockManager, $repoDB, $repoSiteId ) {
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
	protected function engageClientLock( Database $db, $lock ) {
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
	protected function releaseClientLock( Database $db, $lock ) {
		return $this->lockManager->unlock( [ $lock ] )->isOK();
	}

	/**
	 * @see SqlChangeDispatchCoordinator::isClientLockUsed()
	 *
	 * @param Database $db The database connection to work on.
	 * @param string $lock The name of the lock to check.
	 *
	 * @return bool false since it's not needed in LockManager-based coordinators
	 */
	protected function isClientLockUsed( Database $db, $lock ) {
		// Not needed
		return false;
	}

}
