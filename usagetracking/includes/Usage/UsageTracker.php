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
	 * @throws UsageTrackerException
	 */
	public function updateUsageForPage( $pageId, array $usages );

	/**
	 * Get the entities used on the given page.
	 *
	 * @param int $pageId
	 *
	 * @return array An associative array mapping aspect identifiers to lists of EntityIds.
	 * @throws UsageTrackerException
	 */
	public function getUsageForPage( $pageId );

	/**
	 * Returns the pages that use any of the given entities.
	 *
	 * @param EntityId[] $entities
	 * @param array $aspects Which aspects to consider (if omitted, all aspects are considered).
	 *
	 * @return int[] A list of page ids.
	 * @throws UsageTrackerException
	 * @todo: FIXME: return a pager!
	 */
	public function getPagesUsing( $entities, $aspects = array() );

}
 