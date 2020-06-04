<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for retrieving EntityRevisions from storage.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface EntityRevisionLookup {

	/**
	 * Returns the entity revision with the provided id or null if there is no such
	 * entity or if access is forbidden. If a $revisionId is given, the requested revision of the entity is loaded.
	 * If that revision does not exist or does not belong to the given entity,
	 * an exception is thrown.
	 *
	 * Implementations of this method must not silently resolve redirects.
	 *
	 * @param EntityId $entityId
	 * @param int|null $revisionId The desired revision id, or 0 for the latest revision.
	 * @param string $mode LookupConstants::LATEST_FROM_REPLICA, LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *        LookupConstants::LATEST_FROM_MASTER.
	 *
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = LookupConstants::LATEST_FROM_REPLICA
	);

	/**
	 * Returns the id of the latest revision of the given entity, or false if there is no such entity.
	 *
	 * Implementations of this method must not silently resolve redirects.
	 * They can however return a LatestRevisionIdResult object containing information about the redirect.
	 *
	 * @param EntityId $entityId
	 * @param string $mode LookupConstants::LATEST_FROM_REPLICA, LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 * LookupConstants::LATEST_FROM_MASTER.
	 * 	LATEST_FROM_MASTER would force the revision to be determined from the canonical master database.
	 *
	 * @return LatestRevisionIdResult
	 */
	public function getLatestRevisionId(
		EntityId $entityId,
		$mode = LookupConstants::LATEST_FROM_REPLICA
	);

}
