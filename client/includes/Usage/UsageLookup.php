<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage;

use Traversable;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface looking up the usage of entities across pages on the local wiki.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface UsageLookup {

	/**
	 * Get the entities used on the given page.
	 *
	 * The returned array uses the {@link EntityUsage::getIdentityString() identity string}
	 * as the key, so that a specific usage can be found quickly.
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[] keyed by identity string
	 */
	public function getUsagesForPage( int $pageId ): array;

	/**
	 * Returns the pages that use any of the given entities.
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects Which aspects to consider (if omitted, all aspects are considered).
	 * Use the EntityUsage::XXX_USAGE constants to represent aspects.
	 *
	 * @return Traversable A traversable over PageEntityUsages of pages using any of the given
	 *  entities. If $aspects is given, only usages of these aspects are included in the result.
	 */
	public function getPagesUsing( array $entityIds, array $aspects = [] ): Traversable;

	/**
	 * Returns the elements of $entityIds that are currently not used as
	 * far as this UsageTracker knows. In other words, this method answers the
	 * question which of a given list of entities are currently being used on
	 * wiki pages.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[] A list of elements of $entityIds that are unused.
	 */
	public function getUnusedEntities( array $entityIds ): array;

}
