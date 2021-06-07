<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Rdbms;

use IDatabase;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikimedia\Rdbms\LBFactorySingle;

/**
 * Trait to be used in Lib integration tests to get a RepoDbFactory where we cannot use the repo/client service getters.
 * @license GPL-2.0-or-later
 */
trait LocalRepoDbTestHelper {

	public function getRepoDomainDbFactoryForDb( IDatabase $db ): RepoDomainDbFactory {
		$lbFactory = LBFactorySingle::newFromConnection( $db );
		$domainId = $lbFactory->getLocalDomainID();

		return new RepoDomainDbFactory(
			$lbFactory,
			$domainId
		);
	}

}
