<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikimedia\Assert\Assert;

/**
 * Implementation of EntityLookup that caches the obtained entities.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CachingEntityRevisionLookup implements EntityRevisionLookup, EntityStoreWatcher {

	/**
	 * @var EntityRevisionCache
	 */
	private $entityRevisionCache;

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * @var CacheRetrievingEntityRevisionLookup
	 */
	private $cacheRetrievingLookup;

	/**
	 * @var bool
	 */
	private $shouldVerifyRevision = false;

	public function __construct(
		EntityRevisionCache $entityRevisionCache,
		EntityRevisionLookup $entityRevisionLookup
	) {
		$this->entityRevisionCache = $entityRevisionCache;
		$this->lookup = $entityRevisionLookup;

		$this->cacheRetrievingLookup = new CacheRetrievingEntityRevisionLookup(
			$entityRevisionCache,
			$entityRevisionLookup
		);
	}

	/**
	 * Determine whether the revision of the cached entity should be verified against the
	 * current revision in the underlying lookup.
	 *
	 * @param bool $shouldVerifyRevision
	 */
	public function setVerifyRevision( $shouldVerifyRevision ) {
		$this->shouldVerifyRevision = $shouldVerifyRevision;
		$this->cacheRetrievingLookup->setVerifyRevision( $shouldVerifyRevision );
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision
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
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = LookupConstants::LATEST_FROM_REPLICA
	) {
		Assert::parameterType( 'integer', $revisionId, '$revisionId' );
		Assert::parameterType( 'string', $mode, '$mode' );

		$entityRevision = $this->cacheRetrievingLookup->getEntityRevisionFromCache( $entityId, $revisionId, $mode );

		if ( $entityRevision === null ) {
			$entityRevision = $this->fetchEntityRevision( $entityId, $revisionId, $mode );
		}

		return $entityRevision;
	}

	/**
	 * Fetches the EntityRevision and updates the cache accordingly.
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId
	 * @param string $mode
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	private function fetchEntityRevision( EntityId $entityId, $revisionId, $mode ) {
		$entityRevision = $this->lookup->getEntityRevision( $entityId, $revisionId, $mode );

		if ( $revisionId === 0 ) {
			if ( $entityRevision === null ) {
				$this->entityRevisionCache->delete( $entityId );
			} else {
				$this->entityRevisionCache->set( $entityRevision );
			}
		}

		return $entityRevision;
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return LatestRevisionIdResult
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = LookupConstants::LATEST_FROM_REPLICA ) {
		return $this->cacheRetrievingLookup->getLatestRevisionId( $entityId, $mode );
	}

	/**
	 * Notifies the cache that an Entity was created or updated.
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$this->entityRevisionCache->set( $entityRevision );
	}

	/**
	 * Notifies the cache that a redirect was created or updated.
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		//TODO: cache redirects
		$this->entityRevisionCache->delete( $entityRedirect->getEntityId() );
	}

	/**
	 * Notifies the cache that an Entity or redirect was deleted.
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$this->entityRevisionCache->delete( $entityId );
		// XXX: if $this->lookup supports purging, purge?
	}

}
