<?php

namespace Wikibase\Repo\Store;

use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Repo\Store\Sql\Terms\DatabaseTermsCollisionDetector;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GPL-2.0-or-later
 */
class TermsCollisionDetectorFactory {

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var TypeIdsLookup */
	private $typeIdsLookup;

	public function __construct(
		ILoadBalancer $loadBalancer,
		TypeIdsLookup $typeIdsLookup
	) {
		$this->loadBalancer = $loadBalancer;
		$this->typeIdsLookup = $typeIdsLookup;
	}

	public function getTermsCollisionDetector( string $entityType ): TermsCollisionDetector {
		return $this->getDatabaseTermsCollisionDetector( $entityType );
	}

	public function getDatabaseTermsCollisionDetector( string $entityType ): DatabaseTermsCollisionDetector {
		return new DatabaseTermsCollisionDetector(
			$entityType,
			$this->loadBalancer,
			$this->typeIdsLookup
		);
	}
}
