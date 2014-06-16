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

	/**
	 * called when an entity is redirected
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function entityRedirected( EntityRedirect $entityRedirect, $revisionId );

}
