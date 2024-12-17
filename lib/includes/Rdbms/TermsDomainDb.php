<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Database abstraction to access terms (labels, descriptions, aliases) database tables created by the WikibaseRepository extension.
 * (This access may happen in repo, client, or lib.)
 *
 * The underlying database is either the same as {@link RepoDomainDb}, or a dedicated virtual domain database.
 *
 * @license GPL-2.0-or-later
 */
class TermsDomainDb {

	private RepoDomainDb $repoDb;

	public function __construct( RepoDomainDb $repoDb ) {
		$this->repoDb = $repoDb;
	}

	public function getWriteConnection( int $flags = 0 ): IDatabase {
		return $this->repoDb->connections()->getWriteConnection( $flags );
	}

	public function getReadConnection( ?array $groups = null, int $flags = 0 ): IReadableDatabase {
		return $this->repoDb->connections()->getReadConnection( $groups, $flags );
	}

	public function waitForReplicationOfAllAffectedClusters( ?int $timeout = null ): void {
		$this->repoDb->replication()->waitForAllAffectedClusters( $timeout );
	}

	/**
	 * @deprecated Don't use this unless it needs to be passed to a service we don't control
	 */
	public function loadBalancer(): ILoadBalancer {
		return $this->repoDb->loadBalancer();
	}

	/**
	 * @deprecated Don't use this unless it needs to be passed to a service we don't control
	 */
	public function domain(): string {
		return $this->repoDb->domain();
	}

}
