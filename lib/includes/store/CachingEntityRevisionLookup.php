<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;

/**
 * Implementation of EntityLookup that caches the obtained entities.
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
	 * @var int $cacheTimeout
	 */
	protected $cacheTimeout;

	/**
	 * The key prefix to use when caching entities in memory.
	 *
	 * @var $cacheKeyPrefix
	 */
	protected $cacheKeyPrefix;

	/**
	 * @var bool $shouldVerifyRevision
	 */
	protected $shouldVerifyRevision;

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
	protected function getCacheKey( EntityId $entityId ) {
		$cacheKey = $this->cacheKeyPrefix . ':' . $entityId->getSerialization();

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

		try {
			$entityRevision = $this->cache->get( $key );
		} catch ( \UnexpectedValueException $ex ) {
			// @fixme ugly hack to work around hhvm issue, see bug 71461, in addition
			// to varying cache key based on if HHVM_VERSION is defined.
			wfLogWarning( 'Failed to deserialize ' . $entityId->getSerialization() . ' from cache.' );
			$entityRevision = false;
		}

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
