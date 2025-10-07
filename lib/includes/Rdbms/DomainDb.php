<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;

/**
 * Encapsulation of access to MediaWiki DB related functionality that is commonly used in Wikibase.
 * Using this class means in most cases only 1 service need be injected into a service instead of 3+
 * which would otherwise be needed to have access to the same functionality.
 *
 * Do not type hint against this abstract class in Wikibase services. Instead, use either
 * ClientDomainDb or RepoDomainDb depending on what kind of wiki is being accessed.
 *
 * @author Addshore
 * @license GPL-2.0-or-later
 */
abstract class DomainDb {
	private ILBFactory $lbFactory;
	private string $domainId;
	private ReplicationWaiter $replicationWaiter;

	/**
	 * @var string[]
	 */
	private array $loadGroups;

	private ?SessionConsistentConnectionManager $sessionConsistentConnectionManager = null;
	private ?ConnectionManager $connectionManager = null;

	public function __construct( ILBFactory $lbFactory, string $domainId ) {
		$this->lbFactory = $lbFactory;
		$this->domainId = $domainId;

		$this->replicationWaiter = new ReplicationWaiter(
			$lbFactory,
			$domainId
		);
	}

	/**
	 * WARNING: Do _not_ override the load-groups in individual method calls on SessionConsistentConnectionManager.
	 * Instead add them to the factory method!
	 */
	public function sessionConsistentConnections(): SessionConsistentConnectionManager {
		if ( $this->sessionConsistentConnectionManager === null ) {
			$this->sessionConsistentConnectionManager = new SessionConsistentConnectionManager(
				$this->lbFactory->getMainLB( $this->domainId ),
				$this->domainId
			);
		}
		return $this->sessionConsistentConnectionManager;
	}

	/**
	 * WARNING: Do _not_ override the load-groups in individual method calls on ConnectionManager!
	 * Instead, add them to the factory method!
	 */
	public function connections(): ConnectionManager {
		if ( $this->connectionManager === null ) {
			$this->connectionManager = new ConnectionManager(
				$this->lbFactory->getMainLB( $this->domainId ),
				$this->domainId
			);
		}
		return $this->connectionManager;
	}

	public function getAutoCommitPrimaryConnection(): IDatabase {
		return $this->lbFactory->getAutoCommitPrimaryConnection( $this->domainId );
	}

	public function replication(): ReplicationWaiter {
		return $this->replicationWaiter;
	}

	/**
	 * Only used in batch writing (replication aware).
	 * See https://www.mediawiki.org/wiki/Database_transactions#Splitting_writes_into_multiple_transactions
	 *
	 * @param string $fname Caller name (e.g. __METHOD__)
	 * @return mixed A value to pass to commitAndWaitForReplication()
	 */
	public function getEmptyTransactionTicket( string $fname ) {
		return $this->lbFactory->getEmptyTransactionTicket( $fname );
	}

	/**
	 * Only used in batch writing (replication aware).
	 * See https://www.mediawiki.org/wiki/Database_transactions#Splitting_writes_into_multiple_transactions
	 *
	 * @param string $fname Caller name (e.g. __METHOD__)
	 * @param mixed $ticket Result of getEmptyTransactionTicket()
	 * @return bool True if the wait was successful, false on timeout
	 */
	public function commitAndWaitForReplication( string $fname, $ticket ): bool {
		return $this->lbFactory->commitAndWaitForReplication( $fname, $ticket );
	}

	/**
	 * Reload the LBFactory configuration.
	 * Long-running processes should call this regularly.
	 *
	 * @see ILBFactory::autoReconfigure
	 */
	public function autoReconfigure(): void {
		$this->lbFactory->autoReconfigure();
	}

	/**
	 * @deprecated Don't use this unless it needs to be passed to a service we don't control
	 */
	public function domain(): string {
		return $this->domainId;
	}

}
