<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityExistenceChecker;

/**
 * @license GPL-2.0-or-later
 */
class ApiEntityExistenceChecker implements EntityExistenceChecker {

	/** @var ApiEntityLookup */
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

	public function existsBatch( array $ids ): array {
		$this->apiEntityLookup->fetchEntities( $ids );

		$ret = [];
		foreach ( $ids as $id ) {
			$ret[$id->getSerialization()] = $this->exists( $id );
		}
		return $ret;
	}
}
