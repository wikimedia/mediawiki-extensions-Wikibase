<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Usage\Sql;

use Wikibase\Client\Usage\Sql\EntityUsageDomainDb;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IDatabase;

/**
 * Creates a local EntityUsageDomainDb for testing X1 db usages.
 *
 * @license GPL-2.0-or-later
 */
trait LocalEntityUsageDbTestHelper {

	protected function getEntityUsageDomainDb( IDatabase $db ): EntityUsageDomainDb {
		$dbProvider = $this->createMock( IConnectionProvider::class );
		$dbProvider->method( 'getPrimaryDatabase' )
			->willReturn( $db );
		$dbProvider->method( 'getReplicaDatabase' )
			->willReturn( $db );
		return new EntityUsageDomainDb( $dbProvider );
	}
}
