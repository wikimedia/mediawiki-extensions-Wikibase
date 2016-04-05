<?php

namespace Wikibase\Client\Usage;

use ArrayIterator;
use Traversable;
use Wikibase\DataModel\Entity\EntityId;

/**
 * No-op implementation of the UsageTracker and UsageLookup interfaces.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class NullUsageTracker implements UsageTracker, UsageLookup {

	/**
	 * @see UsageTracker::trackUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string $touched
	 */
	public function trackUsedEntities( $pageId, array $usages, $touched ) {
		// no-op
	}

	/**
	 * @see UsageTracker::pruneStaleUsages
	 *
	 * @param int $pageId
	 * @param string $lastUpdatedBefore
	 *
	 * @return EntityUsage[]
	 */
	public function pruneStaleUsages( $pageId, $lastUpdatedBefore ) {
		return [];
	}

	/**
	 * @see UsageTracker::getUsagesForPage
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 */
	public function getUsagesForPage( $pageId ) {
		return [];
	}

	/**
	 * @see UsageLookup::getUnusedEntities
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[]
	 */
	public function getUnusedEntities( array $entityIds ) {
		return [];
	}

	/**
	 * @see UsageLookup::getPagesUsing
	 *
	 * @param EntityId[] $entities
	 * @param string[] $aspects
	 *
	 * @return Traversable Always empty.
	 */
	public function getPagesUsing( array $entities, array $aspects = [] ) {
		return new ArrayIterator();
	}

}
