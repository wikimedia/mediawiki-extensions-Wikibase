<?php

namespace Wikibase\Repo\Store;

use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Repo\Store\Sql\Terms\DatabaseTermsCollisionDetector;

/**
 * @license GPL-2.0-or-later
 */
class TermsCollisionDetectorFactory {

	/** @var RepoDomainDb */
	private $db;

	/** @var TypeIdsLookup */
	private $typeIdsLookup;

	public function __construct(
		RepoDomainDb $db,
		TypeIdsLookup $typeIdsLookup
	) {
		$this->db = $db;
		$this->typeIdsLookup = $typeIdsLookup;
	}

	public function getTermsCollisionDetector( string $entityType ): TermsCollisionDetector {
		return $this->getDatabaseTermsCollisionDetector( $entityType );
	}

	public function getDatabaseTermsCollisionDetector( string $entityType ): DatabaseTermsCollisionDetector {
		return new DatabaseTermsCollisionDetector(
			$entityType,
			$this->db,
			$this->typeIdsLookup
		);
	}
}
