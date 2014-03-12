<?php

namespace Wikibase;

/**
 * A service for paging through EntityIds.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityIdPager {

	/**
	 * Lists the IDs of entities of the given type.
	 *
	 * This supports paging via the $offset parameter: to get the next batch of IDs,
	 * call listEntities() again with the $offset provided by the previous call
	 * to listEntities().
	 *
	 * @since 0.5
	 *
	 * @param string|null $entityType The type of entity to return, or null for any type.
	 * @param int $limit The maximum number of IDs to return.
	 * @param mixed &$offset A position marker representing the position to start listing from;
	 * Will be updated to represent the position for the next batch of IDs.
	 * Callers should make no assumptions about the type or content of $offset.
	 * Use null (the default) to start with the first ID.
	 *
	 * @return EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function listEntities( $entityType, $limit, &$offset = null );
}
