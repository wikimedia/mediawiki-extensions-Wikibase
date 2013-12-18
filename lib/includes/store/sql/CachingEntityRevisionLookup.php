<?php

namespace Wikibase\store;

use BagOStuff;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\EntityRevision;
use Wikibase\EntityRevisionLookup;

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
	 * @var bool $verifyRevision
	 */
	protected $verifyRevision;

	/**
	 * @param EntityRevisionLookup $lookup The lookup to use
	 * @param \BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds.
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *         Defaults to "wbentity". There should be no reason to change this.
	 *
	 * @return \Wikibase\store\CachingEntityRevisionLookup
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
	 * @param boolean $verifyRevision
	 */
	public function setVerifyRevision( $verifyRevision ) {
		$this->verifyRevision = $verifyRevision;
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
	 * still the latest. Otherwise, any cached revision will be used if $revision=0.
	 *
	 * @param EntityId $entityId
	 * @param int      $revision The desired revision id, 0 means "current".
	 *
	 * @return EntityRevision|null
	 *
	 * @throw StorageException
	 */
	public function getEntityRevision( EntityId $entityId, $revision = 0 ) {
		wfProfileIn( __METHOD__ );
		$key = $this->getCacheKey( $entityId );

		$entityRevision = $this->cache->get( $key );

		$isLatest = ( $revision === 0 );

		if ( $entityRevision !== false ) {
			if ( $revision === 0  && $this->verifyRevision ) {
				$revision = $this->lookup->getLatestRevisionId( $entityId );
			}

			if ( $revision !== 0 && $entityRevision->getRevision() !== $revision ) {
				$entityRevision = false;
			}
		}

		if ( $entityRevision === false ) {
			wfProfileIn( __METHOD__ . '#miss' );
			$entityRevision = $this->lookup->getEntityRevision( $entityId, $revision );

			if ( $isLatest ) {
				$this->cache->set( $key, $entityRevision, $this->cacheTimeout );
			} else {
				$this->cache->add( $key, $entityRevision, $this->cacheTimeout );
			}

			wfProfileOut( __METHOD__ . '#miss' );
		}

		wfProfileOut( __METHOD__ );
		return $entityRevision;
	}

	/**
	 * @since 0.5
	 * @see   EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 * @param int      $revision The desired revision id, 0 means "current".
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityId $entityId, $revision = 0 ) {
		$entityRevision = $this->getEntityRevision( $entityId, $revision );
		return $entityRevision === null ? null : $entityRevision->getEntity();
	}

	/**
	 * See EntityLookup::hasEntity()
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
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
		if ( !$this->verifyRevision ) {
			$key = $this->getCacheKey( $entityId );
			$entityRevision = $this->cache->get( $key );

			if ( $entityRevision !== false ) {
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
	 * Notifies the cache that an entity was deleted.
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$key = $this->getCacheKey( $entityId );
		$this->cache->delete( $key );
	}
}
