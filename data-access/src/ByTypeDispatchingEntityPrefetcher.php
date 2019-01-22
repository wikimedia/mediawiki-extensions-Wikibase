<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityPrefetcher implements EntityPrefetcher {

	/**
	 * @var EntityPrefetcher[]
	 */
	private $prefetchers;

	public function __construct( array $prefetchers ) {
		Assert::parameterElementType( EntityPrefetcher::class, $prefetchers, '$prefetchers' );
		Assert::parameterElementType( 'string', array_keys( $prefetchers ), 'keys of $prefetchers' );

		$this->prefetchers = $prefetchers;
	}

	/**
	 * @param EntityId[] $entityIds
	 */
	public function prefetch( array $entityIds ) {
		$groupedEntityIds = [];
		foreach ( $entityIds as $id ) {
			$groupedEntityIds[$id->getEntityType()][] = $id;
		}
		foreach ( $groupedEntityIds as $type => $ids ) {
			$prefetcher = $this->getServiceForEntityType( $type );
			if ( $prefetcher !== null ) {
				$prefetcher->prefetch( $ids );
			}
		}
	}

	public function purge( EntityId $entityId ) {
		$prefetcher = $this->getServiceForEntityType( $entityId->getEntityType() );
		if ( $prefetcher !== null ) {
			$prefetcher->purge( $entityId );
		}
	}

	public function purgeAll() {
		foreach ( $this->prefetchers as $prefetcher ) {
			$prefetcher->purgeAll();
		}
	}

	/**
	 * @param string $entityType
	 * @return EntityPrefetcher|null
	 */
	private function getServiceForEntityType( $entityType ) {
		if ( array_key_exists( $entityType, $this->prefetchers ) ) {
			return $this->prefetchers[$entityType];
		}
		return null;
	}

}
