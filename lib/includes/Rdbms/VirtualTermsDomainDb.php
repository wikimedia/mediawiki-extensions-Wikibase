<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Accesses terms (labels, descriptions, aliases) database tables via virtual domain.
 *
 * @license GPL-2.0-or-later
 */
class VirtualTermsDomainDb implements TermsDomainDb {

	public const VIRTUAL_DOMAIN_ID = 'virtual-wikibase-terms';

	private ILBFactory $lbFactory;

	public function __construct( ILBFactory $lbFactory ) {
		$this->lbFactory = $lbFactory;
	}

	public function getWriteConnection(): IDatabase {
		return $this->lbFactory->getPrimaryDatabase( self::VIRTUAL_DOMAIN_ID );
	}

	public function getAutoCommitPrimaryConnection(): IDatabase {
		return $this->lbFactory->getAutoCommitPrimaryConnection( self::VIRTUAL_DOMAIN_ID );
	}

	public function getReadConnection( ?array $groups = null ): IReadableDatabase {
		return $this->lbFactory->getReplicaDatabase( self::VIRTUAL_DOMAIN_ID );
	}

	public function waitForReplicationOfAllAffectedClusters( ?int $timeout = null ): void {
		$this->lbFactory->waitForReplication( array_filter( [
			'timeout' => $timeout,
		] ) );
	}
}
