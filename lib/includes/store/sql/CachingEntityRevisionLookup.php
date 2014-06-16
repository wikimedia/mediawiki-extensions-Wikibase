<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;

/**
 * Implementation of EntityLookup that caches the obtained entities in memory.
 * The cache is never invalidated or purged. There is no size limit.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CachingEntityRevisionLookup implements EntityRevisionLookup, EntityStoreWatcher {

	/**
	 * @var EntityRevisionLookup
	 */
	protected $lookup;

	/**
	 * The cache to use for caching entities.
	 *
	 * @var BagOStuff
	 */
	protected $cache;

	/**
	 * The key prefix to use when caching entities in memory.
	 *
	 * @var $cacheKeyPrefix
	 */
	protected $cacheKeyPrefix;

	/**
	 * @var int $cacheTimeout
	 */
	protected $cacheTimeout;

	/**
	 * @var bool $shouldVerifyRevision
	 */
	protected $shouldVerifyRevision;

	/**
	 * @param EntityRevisionLookup $lookup The lookup to use
	 * @param BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds.
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbentity". There should be no reason to change this.
	 *
	 * @return \Wikibase\Lib\Store\CachingEntityRevisionLookup
	 */
	public function __construct( EntityRevisionLookup $lookup, BagOStuff $cache, $cacheDuration = 3600, $cacheKeyPrefix = "wbentity" ) {
		$this->lookup = $lookup;
		$this->cache = $cache;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
		$this->cacheTimeout = $cacheDuration;
	}

	/**
	 * Determine whether the revision of the cached entity should be verified against the
	 * current revision in the underlying lookup.
	 *
	 * @param boolean $shouldVerifyRevision
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
	protected function getCacheKey( EntityId $entityId ) {
		$cacheKey = $this->cacheKeyPrefix
			. ':' . $entityId->getSerialization();

		return $cacheKey;
	}

	/**
	 * @see   EntityLookup::getEntity
	 *
	 * @note: If this lookup is configured to verify revisions, getLatestRevisionId()
	 * will be called on the underlying lookup to check whether the cached revision is
	 * still the latest. Otherwise, any cached revision will be used if $revisionId=0.
	 *
	 * @param EntityId $entityId
	 * @param int      $revisionId The desired revision id, 0 means "current".
	 *
	 * @throw StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = 0 ) {
		wfProfileIn( __METHOD__ );
		$key = $this->getCacheKey( $entityId );

		$entityRevision = $this->cache->get( $key );

		if ( $entityRevision !== false ) {
			if ( $revisionId === 0  && $this->shouldVerifyRevision ) {
				$revisionId = $this->lookup->getLatestRevisionId( $entityId );

				if ( $revisionId === false ) {
					// entity no longer exists!
					$entityRevision = null;
					$revisionId = 0;
				}
			}

			if ( $revisionId !== 0 && $entityRevision->getRevision() !== $revisionId ) {
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
	 * Fetches the EntityRevision and updates the cache accordingly.
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId
	 *
	 * @throw StorageException
	 * @return null|EntityRevision
	 */
	private function fetchEntityRevision( EntityId $entityId, $revisionId = 0 ) {
		wfProfileIn( __METHOD__ );

		$key = $this->getCacheKey( $entityId );
		$entityRevision = $this->lookup->getEntityRevision( $entityId, $revisionId );

		if ( $revisionId === 0 ) {
			if ( $entityRevision === null ) {
				$this->cache->delete( $key );
			} else {
				$this->cache->set( $key, $entityRevision, $this->cacheTimeout );
			}
		} elseif ( $entityRevision ) {
			$this->cache->add( $key, $entityRevision, $this->cacheTimeout );
		}

		wfProfileOut( __METHOD__ );
		return $entityRevision;
	}

	/**
	 * @since 0.5
	 * @see   EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 * @param int      $revisionId The desired revision id, 0 means "current".
	 *
	 * @throw StorageException
	 * @return Entity|null
	 */
	public function getEntity( EntityId $entityId, $revisionId = 0 ) {
		$entityRevision = $this->getEntityRevision( $entityId, $revisionId );
		return $entityRevision === null ? null : $entityRevision->getEntity();
	}

	/**
	 * See EntityLookup::hasEntity()
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @throw StorageException
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		wfProfileIn( __METHOD__ );
		$key = $this->getCacheKey( $entityId );

		if ( $this->cache->get( $key ) ) {
			$has = true;
		} else {
			$has = $this->lookup->getEntityRevision( $entityId ) !== null;
		}

		wfProfileOut( __METHOD__ );
		return $has;
	}

	/**
	 * Returns the id of the latest revision of the given entity,
	 * or false if there is no such entity.
	 *
	 * @note: If this lookup is configured to verify revisions, this just delegates
	 * to the underlying lookup. Otherwise, it may return the ID of a cached
	 * revision.
	 *
	 * @param EntityId $entityId
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId ) {
		if ( !$this->shouldVerifyRevision ) {
			$key = $this->getCacheKey( $entityId );
			$entityRevision = $this->cache->get( $key );

			if ( $entityRevision ) {
				return $entityRevision->getRevision();
			}
		}

		return $this->lookup->getLatestRevisionId( $entityId );
	}

	/**
	 * Notifies the cache that an entity was updated.
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$key = $this->getCacheKey( $entityRevision->getEntity()->getId() );
		$this->cache->set( $key, $entityRevision, $this->cacheTimeout );
	}

	/**
	 * Notifies the cache that an entity was redirected.
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function entityRedirected( EntityRedirect $entityRedirect, $revisionId ) {
		//TODO: cache redirects
		$key = $this->getCacheKey( $entityRedirect->getEntityId() );
		$this->cache->delete( $key );
	}

	/**
	 * Notifies the cache that an entity was deleted.
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$key = $this->getCacheKey( $entityId );
		$this->cache->delete( $key );
		// XXX: if $this->lookup supports purging, purge?
	}
}
