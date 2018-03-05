<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;

/**
 * Watcher interface for watching an EntityStore.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface EntityStoreWatcher {

	/**
	 * Called when an entity is updated, created, or replaces a redirect.
	 * This is not called when an entity is deleted or replaced by a redirect.
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision );

	/**
	 * Called when a redirect is updated, created, or replaces an entity.
	 * Not called when a redirect is deleted or replaced by an entity.
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId );

	/**
	 * Called when an entity or redirect is deleted.
	 * This is not called when an entity is replaced by a redirect or vice versa.
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId );

}
