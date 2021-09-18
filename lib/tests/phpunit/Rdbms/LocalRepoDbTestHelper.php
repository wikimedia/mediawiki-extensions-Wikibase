<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Rdbms;

use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactorySingle;

/**
 * Trait to get a RepoDbFactory/RepoDomainDb where we cannot use service getters.
 * @license GPL-2.0-or-later
 */
trait LocalRepoDbTestHelper {

	public function getRepoDomainDb( IDatabase $db = null ): RepoDomainDb {
		$lbFactory = LBFactorySingle::newFromConnection( $db ?: $this->db );
		return new RepoDomainDb(
			$lbFactory,
			$lbFactory->getLocalDomainID()
		);
	}

	public function getRepoDomainDbFactory( IDatabase $db = null ): RepoDomainDbFactory {
		$lbFactory = LBFactorySingle::newFromConnection( $db ?: $this->db );
		$domainId = $lbFactory->getLocalDomainID();

		return new RepoDomainDbFactory(
			$lbFactory,
			$domainId
		);
	}

}
