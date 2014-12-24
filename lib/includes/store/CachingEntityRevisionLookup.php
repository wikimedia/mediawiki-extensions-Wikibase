<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\EntityId;
use InvalidArgumentException;
use Wikibase\EntityRevision;

/**
 * Implementation of EntityLookup that caches the obtained entities.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class CachingEntityRevisionLookup implements EntityRevisionLookup, EntityStoreWatcher {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * The cache to use for caching entities.
	 *
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $cacheTimeout;

	/**
	 * The key prefix to use when caching entities in memory.
	 *
	 * @var string
	 */
	private $cacheKeyPrefix;

	/**
	 * @var bool
	 */
	private $shouldVerifyRevision = false;

	/**
	 * @param EntityRevisionLookup $entityRevisionLookup The lookup to use
	 * @param BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds. Defaults to 3600 (1 hour).
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbentity". There should be no reason to change this.
	 */
	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		BagOStuff $cache,
		$cacheDuration = 3600,
		$cacheKeyPrefix = 'wbentity'
	) {
		$this->lookup = $entityRevisionLookup;
		$this->cache = $cache;
		$this->cacheTimeout = $cacheDuration;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	/**
	 * Determine whether the revision of the cached entity should be verified against the
	 * current revision in the underlying lookup.
	 *
	 * @param bool $shouldVerifyRevision
	 */
	public function setVerifyRevision( $shouldVerifyRevision ) {
		$this->shouldVerifyRevision = $shouldVerifyRevision;
	}

	/**
	 * Returns a cache key suitable for the given entity
	 *
	 * @param EntityId $entityId
	 *
	 * @return String
	 */
	private function getCacheKey( EntityId $entityId ) {
		$cacheKey = $this->cacheKeyPrefix . ':' . $entityId->getSerialization();

		return $cacheKey;
	}

	/**
	 * @see EntityLookup::getEntity
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @note: If this lookup is configured to verify revisions, getLatestRevisionId()
	 * will be called on the underlying lookup to check whether the cached revision is
	 * still the latest. Otherwise, any cached revision will be used if $revisionId=0.
	 *
	 * @param EntityId $entityId
	 * @param int|string $revisionId The desired revision id, or LATEST_FROM_SLAVE or LATEST_FROM_MASTER.
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = self::LATEST_FROM_SLAVE ) {
		wfProfileIn( __METHOD__ );
		$key = $this->getCacheKey( $entityId );

		if ( $revisionId === 0 ) {
			$revisionId = self::LATEST_FROM_SLAVE;
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = $this->cache->get( $key );

		if ( $entityRevision !== false ) {
			if ( !is_int( $revisionId ) && $this->shouldVerifyRevision ) {
				$latestRevision = $this->lookup->getLatestRevisionId( $entityId, $revisionId );

				if ( $latestRevision === false ) {
					// entity no longer exists!
					$entityRevision = null;
				} else {
					$revisionId = $latestRevision;
				}
			}

			if ( is_int( $revisionId ) && $entityRevision && $entityRevision->getRevisionId() !== $revisionId ) {
				$entityRevision = false;
			}
		}

		if ( $entityRevision === false ) {
			$entityRevision = $this->fetchEntityRevision( $entityId, $revisionId );
		}

		wfProfileOut( __METHOD__ );
		return $entityRevision;
	}

	/**
	 * @see EntityLookup::getEntityRevisions
	 *
	 * @since 0.5
	 *
	 * @param EntityId[] $entityIds
	 * @param string $from LATEST_FROM_SLAVE or LATEST_FROM_MASTER. LATEST_FROM_MASTER would
	 *        force the revision to be determined from the canonical master database.
	 *
	 * @throws StorageException
	 * @throws InvalidArgumentException
	 * @return array entityid -> EntityRevision, EntityRedirect or null (if not found)
	 */
	public function getEntityRevisions( array $entityIds, $mode = self::LATEST_FROM_SLAVE ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		$cacheKeys = array();

		foreach ( $entityIds as $entityId ) {
			if ( !$entityId instanceof EntityId ) {
				throw new InvalidArgumentException( '$entityIds needs to be an array of EntityIds' );
			}

			$cacheKeys[$entityId->getSerialization()] = $this->getCacheKey( $entityId );
		}
		$entityIds = array_combine( array_keys( $cacheKeys ), $entityIds );

		$cachedEntityRevisions = $this->cache->getMulti( $cacheKeys );
		$entityRevisions = array();
		$latestRevisionsToLoad = array();

		foreach ( $cacheKeys as $id => $key ) {
			if ( !isset( $cachedEntityRevisions[$key] ) || !$cachedEntityRevisions[$key] ) {
				$entityRevisions[$id] = false;
				continue;
			}

			$latestRevisionsToLoad[] = $entityIds[$id];
			$entityRevisions[$id] = $cachedEntityRevisions[$key];
		}

		$latestRevisions = array();
		if ( $this->shouldVerifyRevision && $latestRevisionsToLoad ) {
			$latestRevisions = $this->lookup->getLatestRevisionIds( $latestRevisionsToLoad, $mode );
		}

		$entityRevisionsToLoad = array();

		/** @var EntityRevision $entityRevision */
		foreach ( $entityRevisions as $id => &$entityRevision ) {
			if ( $entityRevision !== false && $this->shouldVerifyRevision ) {
				$latestRevision = $latestRevisions[$id];

				if ( $latestRevision === false ) {
					// entity no longer exists!
					$entityRevision = null;
				} elseif ( $entityRevision && $entityRevision->getRevisionId() !== $latestRevision ) {
					$entityRevision = false;
				}
			}

			if ( $entityRevision === false ) {
				$entityRevisionsToLoad[] = $entityIds[$id];
			}
		}

		if ( $entityRevisionsToLoad ) {
			$entityRevisions = array_merge(
				$entityRevisions,
				$this->fetchEntityRevisions( $entityRevisionsToLoad, $mode )
			);
		}

		return $entityRevisions;
	}

	/**
	 * Fetches the EntityRevision and updates the cache accordingly.
	 *
	 * @param EntityId $entityId
	 * @param int|string $revisionId
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	private function fetchEntityRevision( EntityId $entityId, $revisionId ) {
		wfProfileIn( __METHOD__ );

		$key = $this->getCacheKey( $entityId );
		$entityRevision = $this->lookup->getEntityRevision( $entityId, $revisionId );

		if ( !is_int( $revisionId ) ) {
			if ( $entityRevision === null ) {
				$this->cache->delete( $key );
			} else {
				$this->cache->set( $key, $entityRevision, $this->cacheTimeout );
			}
		}

		wfProfileOut( __METHOD__ );
		return $entityRevision;
	}

	/**
	 * Fetches the EntityRevisions and updates the cache accordingly.
	 *
	 * @param EntityId[] $entityIds
	 * @param string $from LATEST_FROM_SLAVE or LATEST_FROM_MASTER. LATEST_FROM_MASTER would
	 *        force the revision to be determined from the canonical master database.
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	private function fetchEntityRevisions( array $entityIds, $from ) {
		$cacheKeys = array();

		foreach ( $entityIds as $entityId ) {
			$cacheKeys[$entityId->getSerialization()] = $this->getCacheKey( $entityId );
		}

		$entityRevisions = $this->lookup->getEntityRevisions( $entityIds, $from );

		$set = array();
		foreach( $entityRevisions as $id => $entityRevision ) {
			$key = $cacheKeys[$id];
			if ( $entityRevision === null ) {
				$this->cache->delete( $key );
			} elseif( $entityRevision instanceof EntityRevision ) {
				$set[$key] = $entityRevision;
			}
		}

		if ( $set ) {
			$this->cache->setMulti( $set, $this->cacheTimeout );
		}

		return $entityRevisions;
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @note: If this lookup is configured to verify revisions, this just delegates
	 * to the underlying lookup. Otherwise, it may return the ID of a cached
	 * revision.
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE ) {
		$result = $this->getLatestRevisionIds( array( $entityId ), $mode );
		return $result[$entityId->getSerialization()];
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionIds
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return array
	 */
	public function getLatestRevisionIds( array $entityIds, $mode = self::LATEST_FROM_SLAVE ) {
		$cacheKeys = array();

		foreach ( $entityIds as $entityId ) {
			if ( !$entityId instanceof EntityId ) {
				throw new InvalidArgumentException( '$entityIds needs to be an array of EntityIds' );
			}

			$cacheKeys[$entityId->getSerialization()] = $this->getCacheKey( $entityId );
		}
		$entityIds = array_combine( array_keys( $cacheKeys ), $entityIds );

		// If we do not need to verify the revision, and the revision isn't
		// needed for an update, we can get the revision from the cached object.
		// XXX: whether this is actually quicker depends on the cache.
		if ( ! ( $this->shouldVerifyRevision || $mode === self::LATEST_FROM_MASTER ) ) {
			$cachedEntityRevisions = $this->cache->getMulti( $cacheKeys );
			$latestRevisions = array();
			$latestRevisionsToLoad = array();

			foreach( $cacheKeys as $id => $key ) {
				if ( !isset( $cachedEntityRevisions[$key] ) || !$cachedEntityRevisions[$key] ) {
					$latestRevisionsToLoad[] = $entityIds[$id];
					continue;
				} else {
					$latestRevisions[$id] = $cachedEntityRevisions[$key]->getRevisionId();
				}
			}

			return array_merge(
				$latestRevisions,
				$this->lookup->getLatestRevisionIds( $latestRevisionsToLoad, $mode )
			);
		}

		return $this->lookup->getLatestRevisionIds( $entityIds, $mode );
	}

	/**
	 * Notifies the cache that an Entity was created or updated.
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$key = $this->getCacheKey( $entityRevision->getEntity()->getId() );
		$this->cache->set( $key, $entityRevision, $this->cacheTimeout );
	}

	/**
	 * Notifies the cache that a redirect was created or updated.
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		//TODO: cache redirects
		$key = $this->getCacheKey( $entityRedirect->getEntityId() );
		$this->cache->delete( $key );
	}

	/**
	 * Notifies the cache that an Entity or redirect was deleted.
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$key = $this->getCacheKey( $entityId );
		$this->cache->delete( $key );
		// XXX: if $this->lookup supports purging, purge?
	}

}
