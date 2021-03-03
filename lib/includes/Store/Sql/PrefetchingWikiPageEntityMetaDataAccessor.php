<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use MapCacheLRU;
use Psr\Log\LoggerInterface;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\LookupConstants;

/**
 * A WikiPageEntityMetaDataAccessor decorator that implements prefetching and caching.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class PrefetchingWikiPageEntityMetaDataAccessor implements
	EntityPrefetcher,
	EntityStoreWatcher,
	WikiPageEntityMetaDataAccessor
{

	/**
	 * @var WikiPageEntityMetaDataAccessor
	 */
	private $lookup;

	/**
	 * @var MapCacheLRU
	 */
	private $cache;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var EntityId[]
	 */
	private $toFetch = [];

	/**
	 * @param WikiPageEntityMetaDataAccessor $lookup
	 * @param LoggerInterface $logger
	 * @param int $maxCacheKeys Maximum number of entries to cache (defaults to 1000)
	 */
	public function __construct( WikiPageEntityMetaDataAccessor $lookup, LoggerInterface $logger, $maxCacheKeys = 1000 ) {
		$this->lookup = $lookup;
		$this->logger = $logger;
		$this->cache = new MapCacheLRU( $maxCacheKeys );
	}

	/**
	 * @param int $newSize
	 */
	private function increaseCacheSize( $newSize ) {
		$this->cache->setMaxSize( $newSize );

		$this->logger->debug(
			'{method}: Needed to increase size of MapCacheLRU instance to {newSize}',
			[
				'method' => __METHOD__,
				'newSize' => $newSize,
			]
		);
	}

	/**
	 * Marks the given entity ids for prefetching.
	 * XXX: This does not obey the definition of the EntityPrefetcher interface, that says this should actually fetch.
	 *
	 * @param EntityId[] $entityIds
	 */
	public function prefetch( array $entityIds ) {
		$entityIdCount = count( $entityIds );

		if ( $entityIdCount > $this->cache->getMaxSize() ) {
			// Ouch... fetching everything wouldn't fit into the cache, thus
			// other functions might not find what they're looking for.
			// Increase the size of MapCacheLRU to mitigate this.
			$this->increaseCacheSize( $entityIdCount + 1 );
		}
		if ( ( $entityIdCount + count( $this->toFetch ) ) > $this->cache->getMaxSize() ) {
			// Fetching everything would exceed the capacity of the cache,
			// thus discard all older entity ids as we can safely ignore these.
			$this->toFetch = [];
		}

		foreach ( $entityIds as $entityId ) {
			$idSerialization = $entityId->getSerialization();
			if ( $this->cache->has( $idSerialization ) ) {
				// Make sure the entities we already know about are not going
				// to be purged, by requesting them.
				$this->cache->get( $idSerialization );
			} else {
				$this->toFetch[$idSerialization] = $entityId;
			}
		}
	}

	/**
	 * @see EntityPrefetcher::purge
	 *
	 * @param EntityId $entityId
	 */
	public function purge( EntityId $entityId ) {
		$this->cache->clear( $entityId->getSerialization() );
	}

	/**
	 * @inheritDoc
	 */
	public function purgeAll() {
		$this->cache->clear();
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * SingleEntitySourceServices assumes that this only needs to be called if the service has
	 * been created as a MapCacheLRU is used internally.
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$this->purge( $entityId );
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * SingleEntitySourceServices assumes that this only needs to be called if the service has
	 * been created as a MapCacheLRU is used internally.
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$this->purge( $entityRevision->getEntity()->getId() );
	}

	/**
	 * @see EntityStoreWatcher::redirectUpdated
	 *
	 * SingleEntitySourceServices assumes that this only needs to be called if the service has
	 * been created as a MapCacheLRU is used internally.
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		$this->purge( $entityRedirect->getEntityId() );
	}

	/**
	 * @see WikiPageEntityMetaDataAccessor::loadRevisionInformation
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode ( LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @return (stdClass|bool)[] Array mapping entity ID serializations to either objects
	 * or false if an entity could not be found.
	 */
	public function loadRevisionInformation( array $entityIds, $mode ) {
		if ( $mode === LookupConstants::LATEST_FROM_MASTER ) {
			// Don't attempt to use the cache in case we are asked to fetch
			// from master. Also don't put load on the master by just fetching
			// everything in $this->toFetch.
			$data = $this->lookup->loadRevisionInformation( $entityIds, $mode );
			// Cache the data, just in case it will be needed again.
			$this->store( $data );
			// Make sure we wont fetch these next time.
			foreach ( $entityIds as $entityId ) {
				$key = $entityId->getSerialization();
				unset( $this->toFetch[$key] );
			}

			return $data;
		}

		$this->prefetch( $entityIds );
		$this->doFetch( $mode );

		$data = [];
		foreach ( $entityIds as $entityId ) {
			$data[$entityId->getSerialization()] = $this->cache->get( $entityId->getSerialization() );
		}

		return $data;
	}

	/**
	 * @see WikiPageEntityMetaDataAccessor::loadRevisionInformationByRevisionId
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId
	 * @param string $mode ( LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @return stdClass|bool false if no such entity exists
	 */
	public function loadRevisionInformationByRevisionId(
		EntityId $entityId,
		$revisionId,
		$mode = LookupConstants::LATEST_FROM_MASTER
	) {
		// Caching this would have little or no benefit, but would be rather complex.
		return $this->lookup->loadRevisionInformationByRevisionId( $entityId, $revisionId, $mode );
	}

	/**
	 * @see WikiPageEntityMetaDataAccessor::loadLatestRevisionIds
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode ( LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @return (int|bool)[] Array of entity ID serialization => revision IDs
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	public function loadLatestRevisionIds( array $entityIds, $mode ) {
		$this->doFetch( $mode );

		$revisionIds = [];

		if ( $mode !== LookupConstants::LATEST_FROM_MASTER ) {
			foreach ( $entityIds as $index => $entityId ) {
				$id = $entityId->getSerialization();
				$data = $this->cache->get( $id );
				if ( $data !== null ) {
					if ( $data->page_is_redirect ) {
						$revisionIds[$id] = false;
					} else {
						$revisionIds[$id] = $data->page_latest;
					}
					unset( $entityIds[$index] );
				}
			}
		}

		if ( $entityIds !== [] ) {
			$revisionIds = array_merge(
				$revisionIds,
				$this->lookup->loadLatestRevisionIds( array_values( $entityIds ), $mode )
			);
			// no caching for these – would require a separate cache, not worth it
		}

		return $revisionIds;
	}

	private function doFetch( $mode ) {
		if ( empty( $this->toFetch ) ) {
			return;
		}

		try {
			$data = $this->lookup->loadRevisionInformation( $this->toFetch, $mode );
		} catch ( InvalidArgumentException $exception ) {
			// Do not store invalid entity ids (causing exceptions in lookup).

			// TODO: if the $exception was of more specific type and provided the relevant entity id,
			// it would possible to only remove the relevant key from toFetch.
			$this->toFetch = [];

			// Re-throw the exception to be handled by caller.
			throw $exception;
		}

		// Store the data, including cache misses
		$this->store( $data );

		// Prune $this->toFetch
		$this->toFetch = [];
	}

	private function store( array $data ) {
		foreach ( $data as $key => $value ) {
			$this->cache->set( $key, $value );
		}
	}

}
