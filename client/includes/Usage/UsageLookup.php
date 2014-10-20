<?php

namespace Wikibase\Client\Usage;

use Iterator;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface looking up the usage of entities across pages on the local wiki.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface UsageLookup {

	/**
	 * Get the entities used on the given page.
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 * @throws UsageTrackerException
	 */
	public function getUsageForPage( $pageId );

	/**
	 * Returns the pages that use any of the given entities.
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects Which aspects to consider (if omitted, all aspects are considered).
	 * Use the EntityUsage::XXX_USAGE constants to represent aspects.
	 *
	 * @return Iterator An iterator over the IDs of pages using (the any of the given aspects of)
	 *         any of the given entities
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entityIds, array $aspects = array() );

	/**
	 * Returns the elements of $entityIds that are currently not used as
	 * far as this UsageTracker knows. In other words, this method answers the
	 * question which of a given list of entities are currently being used on
	 * wiki pages.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[] A list of elements of $entityIds that are unused.
	 * @throws UsageTrackerException
	 */
	public function getUnusedEntities( array $entityIds );

}
