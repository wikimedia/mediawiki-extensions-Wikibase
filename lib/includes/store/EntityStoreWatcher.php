<?php

namespace Wikibase\store;

use Wikibase\EntityId;
use Wikibase\EntityRevision;

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
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision );

	/**
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId );
}
