<?php

namespace Wikibase\DataModel\Services\Entity;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A service interface for prefetching entities or data about them in order
 * to make subsequent loading of them faster.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
interface EntityPrefetcher {

	/**
	 * Prefetches data for a list of entity ids.
	 *
	 * @param EntityId[] $entityIds
	 */
	public function prefetch( array $entityIds );

	/**
	 * Purges prefetched data about a given entity.
	 *
	 * @param EntityId $entityId
	 */
	public function purge( EntityId $entityId );

	/**
	 * Purges all prefetched data.
	 */
	public function purgeAll();

}
