<?php

namespace Wikibase;
use BagOStuff;
use MWException;

/**
 * Implementation of EntityLookup that caches the obtained entities in memory.
 * The cache is never invalidated or purged. There is no size limit.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CachingEntityRevisionLookup implements EntityRevisionLookup {

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
	 * @param EntityRevisionLookup $lookup The lookup to use
	 * @param \BagOStuff $cache The cache to use
	 * @param int $cacheDuration Cache duration in seconds.
	 * @param string $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *                                    Defaults to "wbentity". There should be no reason to change this.
	 *
	 * @return \Wikibase\CachingEntityRevisionLookup
	 */
	public function __construct( EntityRevisionLookup $lookup, BagOStuff $cache, $cacheDuration = 3600, $cacheKeyPrefix = "wbentity" ) {
		$this->lookup = $lookup;
		$this->cache = $cache;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
		$this->cacheTimeout = $cacheDuration;
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
	 * @since 0.5
	 * @see   EntityLookup::getEntity
	 *
	 * @param EntityID $entityId
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

		if ( $entityRevision !== null ) {
			if ( $revision === 0 ) {
				$revision = $this->lookup->getLatestRevisionId( $entityId );
			}

			if ( $entityRevision->getRevision() !== $revision ) {
				$entityRevision = null;
			}
		}

		if ( $entityRevision === null ) {
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
	 * @param EntityID $entityId
	 * @param int      $revision The desired revision id, 0 means "current".
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityId $entityId, $revision = 0 ) {
		$entityRevision = $this->getEntityRevision( $entityId, $revision );
		return $entityRev === null ? null : $entityRevision->getEntity();
	}

	/**
	 * See EntityLookup::hasEntity()
	 *
	 * @since 0.4
	 *
	 * @param EntityID $entityId
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

	// TODO: define and implement EntityUpdateListener interface
}
