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
 */
interface EntityRevisionLookup {

	const FOR_DISPLAY = 'display';
	const FOR_UPDATE = 'update';

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
	 * @param int|string $revisionId The desired revision id, or FOR_DISPLAY or FOR_UPDATE
	 *        to indicate that the latest revision is required. FOR_UPDATE would force the
	 *        revision to be determined from the canonical master database.
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = self::FOR_DISPLAY );

	/**
	 * Returns the id of the latest revision of the given entity, or false if there is no such entity.
	 *
	 * Implementations of this method must not silently resolve redirects.
	 *
	 * @param EntityId $entityId
	 * @param string $mode FOR_DISPLAY or FOR_UPDATE. FOR_UPDATE would force the
	 *        revision to be determined from the canonical master database.
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::FOR_DISPLAY );

}
