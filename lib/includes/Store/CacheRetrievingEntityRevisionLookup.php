<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;

/**
 * EntityRevisionLookup implementation that checks an EntityRevisionCache for cached revisions (but
 * doesn't cache on its own). Falls back to a given EntityRevisionLookup.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class CacheRetrievingEntityRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var EntityRevisionCache
	 */
	private $cache;

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * @var bool
	 */
	private $shouldVerifyRevision = false;

	public function __construct( EntityRevisionCache $cache, EntityRevisionLookup $lookup ) {
		$this->cache = $cache;
		$this->lookup = $lookup;
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
	 * Get an EntityRevision from cache or (otherwise) from the underlying EntityRevisionLookup.
	 *
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @note If this lookup is configured to verify revisions, getLatestRevisionId()
	 * will be called on the underlying lookup to check whether the cached revision is
	 * still the latest. Otherwise, any cached revision will be used if $revisionId=0.
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, or 0 for the latest revision.
	 * @param string $mode One of the EntityRevisionLookup::LATEST_* constants.
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = LookupConstants::LATEST_FROM_REPLICA
	) {
		Assert::parameterType( 'integer', $revisionId, '$revisionId' );
		Assert::parameterType( 'string', $mode, '$mode' );

		$entityRevision = $this->getEntityRevisionFromCache( $entityId, $revisionId, $mode );

		if ( $entityRevision === null ) {
			$entityRevision = $this->lookup->getEntityRevision( $entityId, $revisionId, $mode );
		}

		return $entityRevision;
	}

	/**
	 * Try to get an EntityRevision from cache.
	 *
	 * @note If this lookup is configured to verify revisions, getLatestRevisionId()
	 * will be called on the underlying lookup to check whether the cached revision is
	 * still the latest. Otherwise, any cached revision will be used if $revisionId=0.
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, or 0 for the latest revision.
	 * @param string $mode LATEST_FROM_REPLICA, LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *        LATEST_FROM_MASTER.
	 *
	 * @throws StorageException
	 * @return EntityRevision|null Null if the EntityRevision in question is not cached.
	 */
	public function getEntityRevisionFromCache(
		EntityId $entityId,
		$revisionId = 0,
		$mode = LookupConstants::LATEST_FROM_REPLICA
	) {
		Assert::parameterType( 'integer', $revisionId, '$revisionId' );
		Assert::parameterType( 'string', $mode, '$mode' );

		$entityRevision = $this->cache->get( $entityId );

		if ( $entityRevision !== null ) {
			if ( $revisionId === 0 && $this->shouldVerifyRevision ) {
				$latestRevisionIdResult = $this->lookup->getLatestRevisionId( $entityId, $mode );
				$returnFalse = function () {
					return false;
				};

				$latestRevision = $latestRevisionIdResult->onConcreteRevision( function ( $revId ) {
					return $revId;
				} )
					->onRedirect( $returnFalse )
					->onNonexistentEntity( $returnFalse )
					->map();

				if ( $latestRevision === false ) {
					// entity no longer exists!
					return null;
				} else {
					$revisionId = $latestRevision;
				}
			}

			if ( $revisionId > 0 && $entityRevision && $entityRevision->getRevisionId() !== $revisionId ) {
				$entityRevision = null;
			}
		}

		return $entityRevision;
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @note If this lookup is configured to verify revisions, this just delegates
	 * to the underlying lookup. Otherwise, it may return the ID of a cached
	 * revision.
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return LatestRevisionIdResult
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = LookupConstants::LATEST_FROM_REPLICA ) {
		// If we do not need to verify the revision, and the revision isn't
		// needed for an update, we can get the revision from the cached object.
		// XXX: whether this is actually quicker depends on the cache.
		if ( !$this->shouldVerifyRevision && $mode !== LookupConstants::LATEST_FROM_MASTER ) {
			$entityRevision = $this->cache->get( $entityId );

			if ( $entityRevision ) {
				return LatestRevisionIdResult::concreteRevision( $entityRevision->getRevisionId(), $entityRevision->getTimestamp() );
			}
		}

		return $this->lookup->getLatestRevisionId( $entityId, $mode );
	}

}
