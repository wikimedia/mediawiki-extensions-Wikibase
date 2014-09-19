<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for tracking the usage of entities across pages on the local wiki.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface UsageTracker {

	/**
	 * Updates entity usage information for the given page.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 * @param EntityUsage[] $usages A list of entity usages.
	 *
	 * See docs/usagetracking.wiki for details.
	 *
	 * @return EntityUsage[] Usages before the update
	 * @throws UsageTrackerException
	 */
	public function trackUsedEntities( $pageId, array $usages );

	/**
	 * Removes usage tracking for the given set of entities.
	 * This is used typically when entities were deleted.
	 * Calling this method more than once on the same entity has no effect.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @throws UsageTrackerException
	 */
	public function removeEntities( array $entityIds );

}
