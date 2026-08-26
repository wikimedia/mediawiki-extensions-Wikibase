<?php

declare( strict_types=1 );

namespace Wikibase\Client\Usage\Sql;

use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Accesses usage (entity_usage) database tables via virtual domain.
 *
 * @license GPL-2.0-or-later
 */
class EntityUsageDomainDb {
	public const VIRTUAL_DOMAIN_ID = 'virtual-wikibase-entityusage';

	public function __construct( private IConnectionProvider $dbProvider ) {
	}

	public function getWriteConnection(): IDatabase {
		return $this->dbProvider->getPrimaryDatabase( self::VIRTUAL_DOMAIN_ID );
	}

	public function getReadConnection( ?array $groups = null ): IReadableDatabase {
		return $this->dbProvider->getReplicaDatabase( self::VIRTUAL_DOMAIN_ID );
	}
}
