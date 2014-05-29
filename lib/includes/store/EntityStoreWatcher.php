<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
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

	public function entityUpdated( EntityRevision $entityRevision );
	public function entityDeleted( EntityId $entityId );

}
