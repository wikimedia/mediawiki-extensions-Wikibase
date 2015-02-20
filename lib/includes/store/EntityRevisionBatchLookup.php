<?php

namespace Wikibase\Lib\Store;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for retrieving multiple EntityRevisions at once from storage.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
interface EntityRevisionBatchLookup {

	/**
	 * Returns an array entityid -> EntityRevision, EntityRedirect or false (if not found).
	 * Please note that this doesn't throw UnresolvedRedirectExceptions but rather returns
	 * EntityRedirect objects for entities that are infact redirects.
	 *
	 * Implementations of this method must not silently resolve redirects.
	 *
	 * @since 0.5
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @throws StorageException
	 * @return array entityid -> EntityRevision, EntityRedirect or null (if not found)
	 */
	public function getEntityRevisions( array $entityIds );

}
