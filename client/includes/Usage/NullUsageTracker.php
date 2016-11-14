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
 * @author Marius Hoch
 */
class NullUsageTracker implements UsageTracker, UsageLookup {

	/**
	 * @see UsageTracker::addUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 */
	public function addUsedEntities( $pageId, array $usages ) {
		// no-op
	}

	/**
	 * @see UsageTracker::replaceUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return EntityUsage[]
	 */
	public function replaceUsedEntities( $pageId, array $usages ) {
		return [];
	}

	/**
	 * @see UsageTracker::pruneUsages
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 */
	public function pruneUsages( $pageId ) {
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
