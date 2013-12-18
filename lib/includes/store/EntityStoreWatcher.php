<?php

namespace Wikibase;

/**
 * Watcher interface for watching an EntityStore.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityStoreWatcher {

	/**
	 * called when an entity is updated
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision );

	/**
	 * called when an entity is deleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId );
}
