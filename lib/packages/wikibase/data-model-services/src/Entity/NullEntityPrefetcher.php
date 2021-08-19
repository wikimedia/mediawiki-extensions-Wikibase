<?php

namespace Wikibase\DataModel\Services\Entity;

use Wikibase\DataModel\Entity\EntityId;

/**
 * No-op EntityPrefetcher
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 *
 * @codeCoverageIgnore
 */
class NullEntityPrefetcher implements EntityPrefetcher {

	/**
	 * Prefetches data for a list of entity ids.
	 *
	 * @param EntityId[] $entityIds
	 */
	public function prefetch( array $entityIds ) {
	}

	/**
	 * Purges prefetched data about a given entity.
	 *
	 * @param EntityId $entityId
	 */
	public function purge( EntityId $entityId ) {
	}

	/**
	 * Purges all prefetched data.
	 */
	public function purgeAll() {
	}

}
