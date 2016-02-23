<?php

namespace Wikibase\Client\Usage;

/**
 * Service interface for tracking the usage of entities across pages on the local wiki.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
interface UsageTracker {

	/**
	 * Adds new entity usage information for the given page. New usage records
	 * are added, but old ones are not removed.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 * @param EntityUsage[] $usages A list of entity usages.
	 *
	 * See docs/usagetracking.wiki for details.
	 *
	 * @throws UsageTrackerException
	 */
	public function addUsedEntities( $pageId, array $usages );

	/**
	 * Replaces entity usage information for the given page.
	 * All usages not present in $usages will be removed.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 * @param EntityUsage[] $usages A list of entity usages.
	 *
	 * See docs/usagetracking.wiki for details.
	 *
	 * @throws UsageTrackerException
	 * @return EntityUsage[] Usages that have been removed
	 */
	public function replaceUsedEntities( $pageId, array $usages );

	/**
	 * Removes all usage tracking entries for a given page.
	 *
	 * @param int $pageId
	 *
	 * @throws UsageTrackerException
	 * @return EntityUsage[] the pruned usages
	 */
	public function pruneUsages( $pageId );

}
