<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\ILBFactory;

/**
 * Encapsulation of access to MediaWiki DB related functionality that is commonly used in Wikibase.
 * Using this class means in most cases only 1 service need be injected into a service instead of 3+
 * which would otherwise be needed to have access to the same functionality.
 *
 * @author Addshore
 * @license GPL-2.0-or-later
 */
abstract class DomainDb {

	public const LOAD_GROUP_FROM_CLIENT = 'from-client';
	public const LOAD_GROUP_FROM_REPO = 'from-repo';

	/**
	 * @var ConnectionManager
	 */
	private $connectionManager;

	/**
	 * @var ReplicationWaiter
	 */
	private $replicationWaiter;

	public function __construct( ILBFactory $lbFactory, string $domainId, array $loadGroups = [] ) {
		$this->connectionManager = new ConnectionManager(
			$lbFactory->getMainLB( $domainId ),
			$domainId,
			$loadGroups
		);
		$this->replicationWaiter = new ReplicationWaiter(
			$lbFactory,
			$domainId
		);
	}

	/**
	 * WARNING: Do _not_ override the load-groups in individual method calls on ConnectionManager.
	 * Instead add them to the factory method!
	 */
	public function connections(): ConnectionManager {
		return $this->connectionManager;
	}

	public function replication(): ReplicationWaiter {
		return $this->replicationWaiter;
	}

}
