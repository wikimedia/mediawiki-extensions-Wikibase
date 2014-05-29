<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;

/**
 * Contains methods for interaction with an entity store.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityRevisionLookup extends EntityLookup {

	/**
	 * Returns the entity revision with the provided id or null if there is no such
	 * entity. If a $revision is given, the requested revision of the entity is loaded.
	 * If that revision does not exist or does not belong to the given entity,
	 * an exception is thrown.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, 0 means "current".
	 *
	 * @throw StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = 0 );

	/**
	 * Returns the id of the latest revision of the given entity, or false if there is no such entity.
	 *
	 * @param EntityId $entityId
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId );

}
