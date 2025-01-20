<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Accesses terms (labels, descriptions, aliases) database tables via {@link RepoDomainDb}.
 *
 * @license GPL-2.0-or-later
 */
class RepoDomainTermsDb implements TermsDomainDb {

	private RepoDomainDb $repoDb;

	public function __construct( RepoDomainDb $repoDb ) {
		$this->repoDb = $repoDb;
	}

	public function getWriteConnection(): IDatabase {
		return $this->repoDb->connections()->getWriteConnection();
	}

	public function getAutoCommitPrimaryConnection(): IDatabase {
		return $this->repoDb->getAutoCommitPrimaryConnection();
	}

	public function getReadConnection( ?array $groups = null ): IReadableDatabase {
		return $this->repoDb->connections()->getReadConnection( $groups );
	}

	public function waitForReplicationOfAllAffectedClusters( ?int $timeout = null ): void {
		$this->repoDb->replication()->waitForAllAffectedClusters( $timeout );
	}

}
