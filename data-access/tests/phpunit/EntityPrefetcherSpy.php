<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;

/**
 * @license GPL-2.0-or-later
 */
class EntityPrefetcherSpy implements EntityPrefetcher {

	private $prefetchedEntities = [];

	/**
	 * @param EntityId[] $entityIds
	 */
	public function prefetch( array $entityIds ) {
		foreach ( $entityIds as $id ) {
			if ( !array_key_exists( $id->getSerialization(), $this->prefetchedEntities ) ) {
				$this->prefetchedEntities[$id->getSerialization()] = $id;
			}
		}
	}

	public function purge( EntityId $entityId ) {
		unset( $this->prefetchedEntities[ $entityId->getSerialization() ] );
	}

	public function purgeAll() {
		$this->prefetchedEntities = [];
	}

	public function getPrefetchedEntities() {
		return array_values( $this->prefetchedEntities );
	}

}
