<?php

namespace Wikibase;

/**
 * A cursor for paging through EntityIds.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityIdPager {

	/**
	 * Lists the next batch of IDs of entities of the given type. Calling this
	 * may have side effects on some underlying resource such as a file pointer or
	 * a database connection. However, calling this multiple times with the same value
	 * for $position should return the same result (unless an external resource was modified
	 * by another process).
	 *
	 * @note: Paging is done using the $position parameter: to get the next batch of IDs,
	 * call getNextBatchOfIds() again with the $position provided by the previous call
	 * to getNextBatchOfIds().
	 *
	 * @since 0.5
	 *
	 * @param string|null $entityType The type of entity to return, or null for any type.
	 * @param int $limit The maximum number of IDs to return.
	 * @param mixed $position A position marker representing the position to start listing from;
	 * Will be updated to represent the position for the next batch of IDs.
	 * Callers should make no assumptions about the type or content of $position.
	 * Use null to start with the first ID (or just provide an uninitialized variable).
	 *
	 * @return EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function getNextBatchOfIds( $entityType, $limit, &$position = null );
}
