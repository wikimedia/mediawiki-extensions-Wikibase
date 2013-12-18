<?php

namespace Wikibase;

/**
 * Defines the methods available on an entity cache.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityCache extends EntityRevisionLookup {

	/**
	 * Purges the given entity from the cache.
	 *
	 * @param EntityId $entityId
	 */
	public function purgeCachedEntity( EntityID $entityId );

	/**
	 * Updates the given entity in the cache.
	 *
	 * @param EntityRevision $entityRev
	 */
	public function updateCachedEntity( EntityRevision $entityRev );
}
