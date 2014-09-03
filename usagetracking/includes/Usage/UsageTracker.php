<?php

namespace Wikibase\Usage;

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
	 * @param array $usages An associative array, mapping aspect identifiers to lists of EntityIds
	 * indicating the entities that are used in the way indicated by that aspect.
	 * Well known aspects are "sitelinks", "label" and "all",
	 * see docs/usagetracking.wiki for details.
	 *
	 * @return array Usages before the update, in the same form as $usages
	 * @throws UsageTrackerException
	 */
	public function trackUsedEntities( $pageId, array $usages );

	/**
	 * Removes usage tracking for the given set of entities.
	 * This is used typically when entities were deleted.
	 *
	 * @param EntityId[] $entities
	 *
	 * @throws UsageTrackerException
	 */
	public function removeEntities( array $entities );

}
 