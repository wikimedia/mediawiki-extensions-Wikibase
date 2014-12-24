<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;

/**
 * Service interface for retrieving EntityRevisions from storage.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
interface EntityRevisionLookup {

	/**
	 * Flag to use instead of an revision ID to indicate that the latest revision is desired,
	 * but a slightly lagged version is acceptable. This would generally be the case when fetching
	 * entities for display.
	 */
	const LATEST_FROM_SLAVE = 'slave';

	/**
	 * Flag to use instead of an revision ID to indicate that the latest revision is desired,
	 * and it is essential to assert that there really is no newer version, to avoid data loss
	 * or conflicts. This would generally be the case when loading an entity for
	 * editing/modification.
	 */
	const LATEST_FROM_MASTER = 'master';

	/**
	 * Returns the entity revision with the provided id or null if there is no such
	 * entity. If a $revision is given, the requested revision of the entity is loaded.
	 * If that revision does not exist or does not belong to the given entity,
	 * an exception is thrown.
	 *
	 * Implementations of this method must not silently resolve redirects.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param int|string $revisionId The desired revision id, or LATEST_FROM_SLAVE or LATEST_FROM_MASTER
	 *        to indicate that the latest revision is required. LATEST_FROM_MASTER would force the
	 *        revision to be determined from the canonical master database.
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = self::LATEST_FROM_SLAVE );

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
	 * @param string $mode LATEST_FROM_SLAVE or LATEST_FROM_MASTER. LATEST_FROM_MASTER would
	 *        force the revision to be determined from the canonical master database.
	 *
	 * @throws StorageException
	 * @return array entityid -> EntityRevision, EntityRedirect or null (if not found)
	 */
	public function getEntityRevisions( array $entityIds, $mode = self::LATEST_FROM_SLAVE );

	/**
	 * Returns the id of the latest revision of the given entity, or false if there is no such entity.
	 *
	 * Implementations of this method must not silently resolve redirects.
	 *
	 * @param EntityId $entityId
	 * @param string $mode LATEST_FROM_SLAVE or LATEST_FROM_MASTER. LATEST_FROM_MASTER would force the
	 *        revision to be determined from the canonical master database.
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE );

	/**
	 * Returns an array with entityid => the id of the latest revisions of the given entities, or
	 * false if there is no such entity.
	 *
	 * Implementations of this method must not silently resolve redirects.
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode LATEST_FROM_SLAVE or LATEST_FROM_MASTER. LATEST_FROM_MASTER would force the
	 *        revision to be determined from the canonical master database.
	 *
	 * @return array
	 */
	public function getLatestRevisionIds( array $entityIds, $mode = self::LATEST_FROM_SLAVE );
}
