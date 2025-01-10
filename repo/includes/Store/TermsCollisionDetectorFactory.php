<?php

namespace Wikibase\Repo\Store;

use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Repo\Store\Sql\Terms\DatabaseTermsCollisionDetector;

/**
 * @license GPL-2.0-or-later
 */
class TermsCollisionDetectorFactory {

	private TermsDomainDb $db;

	public function __construct( TermsDomainDb $db ) {
		$this->db = $db;
	}

	public function getTermsCollisionDetector( string $entityType ): TermsCollisionDetector {
		return $this->getDatabaseTermsCollisionDetector( $entityType );
	}

	public function getDatabaseTermsCollisionDetector( string $entityType ): DatabaseTermsCollisionDetector {
		return new DatabaseTermsCollisionDetector(
			$entityType,
			$this->db
		);
	}
}
