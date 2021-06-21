<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Exception;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactorySingle;

/**
 * A {@link DomainDb} to access a repo wiki.
 *
 * Use this class to access database tables created by the WikibaseRepository extension.
 * or otherwise belonging to a repo wiki.
 * (This access may happen in repo, client, or lib.)
 *
 * @license GPL-2.0-or-later
 */
class RepoDomainDb extends DomainDb {

	/**
	 * Convenience method for getting a RepoDomainDB in tests
	 *
	 * @param IDatabase $db
	 * @return RepoDomainDb
	 * @throws Exception
	 */
	public static function newFromTestConnection( IDatabase $db ): RepoDomainDb {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new Exception( __METHOD__ . '() should be called only from tests!' );
		}

		$lbFactory = LBFactorySingle::newFromConnection( $db );
		return new RepoDomainDb( $lbFactory, $lbFactory->getLocalDomainID() );
	}

}
