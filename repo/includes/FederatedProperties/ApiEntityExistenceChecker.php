<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityExistenceChecker;

/**
 * @license GPL-2.0-or-later
 */
class ApiEntityExistenceChecker implements EntityExistenceChecker {

	private $apiEntityLookup;

	public function __construct( ApiEntityLookup $apiEntityLookup ) {
		$this->apiEntityLookup = $apiEntityLookup;
	}

	public function exists( EntityId $id ): bool {
		return !array_key_exists(
			'missing',
			$this->apiEntityLookup->getResultPartForId( $id )
		);
	}
}
