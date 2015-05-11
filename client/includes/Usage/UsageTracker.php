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
	 * Updates entity usage information for the given page. New usage records
	 * are added, but old ones may or may not be physically removed. Implementations
	 * are free to rely on passive purging based on the $touched timestamp.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 * @param EntityUsage[] $usages A list of entity usages.
	 * @param string $touched Timestamp corresponding to page.page_touched.
	 *
	 * See docs/usagetracking.wiki for details.
	 *
	 * @throws UsageTrackerException
	 */
	public function trackUsedEntities( $pageId, array $usages, $touched );

	/**
	 * Removes usage tracking entries that were last updated before the given
	 * timestamp.
	 *
	 * @param int $pageId
	 * @param string $lastUpdatedBefore
	 *
	 * @throws UsageTrackerException
	 */
	public function pruneStaleUsages( $pageId, $lastUpdatedBefore );

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
