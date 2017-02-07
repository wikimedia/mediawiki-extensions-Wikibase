<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikimedia\Assert\Assert;

/**
 * Delegates prefetching and purge requests to the EntityPrefetcher instance configured
 * for a repository the entity ID belongs to.
 * When purgeAll is called all known prefetchers are requested to purge their caches.
 *
 * @license GPL-2.0+
 */
class DispatchingEntityPrefetcher implements EntityPrefetcher {

	/**
	 * @var EntityPrefetcher[]
	 */
	private $prefetchers;

	public function __construct( array $prefetchers ) {
		Assert::parameter( $prefetchers !== [], '$prefetchers', 'must not be empty' );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $prefetchers, '$prefetchers' );
		Assert::parameterElementType( EntityPrefetcher::class, $prefetchers, '$prefetchers' );

		$this->prefetchers = $prefetchers;
	}

	/**
	 * @see EntityPrefetcher::prefetch
	 *
	 * @param EntityId[] $entityIds
	 */
	public function prefetch( array $entityIds ) {
		$groupedEntityIds = $this->groupEntityIdsByRepository( $entityIds );

		foreach ( $groupedEntityIds as $repositoryName => $ids ) {
			$prefetcher = $this->getPrefetcherForRepository( $repositoryName );

			if ( $prefetcher !== null ) {
				$prefetcher->prefetch( $ids );
			}
		}
	}

	/**
	 * @see EntityPrefetcher::purge
	 *
	 * @param EntityId $entityId
	 */
	public function purge( EntityId $entityId ) {
		$prefetcher = $this->getPrefetcherForRepository( $entityId->getRepositoryName() );
		if ( $prefetcher !== null ) {
			$prefetcher->purge( $entityId );
		}
	}

	/**
	 * @see EntityPrefetcher::purgeAll
	 */
	public function purgeAll() {
		foreach ( $this->prefetchers as $prefetcher ) {
			$prefetcher->purgeAll();
		}
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return EntityPrefetcher|null
	 */
	private function getPrefetcherForRepository( $repositoryName ) {
		return isset( $this->prefetchers[$repositoryName ] ) ? $this->prefetchers[$repositoryName] : null;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return array[]
	 */
	private function groupEntityIdsByRepository( array $entityIds ) {
		$entityIdsByRepository = [];

		foreach ( $entityIds as $id ) {
			$repository = $id->getRepositoryName();
			$entityIdsByRepository[$repository][] = $id;
		}

		return $entityIdsByRepository;
	}

}
