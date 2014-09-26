<?php

namespace Wikibase\Repo\Store;

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
	 * Fetches the next batch of IDs. Calling this has the side effect of advancing the
	 * internal state of the page, typically implemented by some underlying resource
	 * such as a file pointer or a database connection.
	 *
	 * @note: After some finite number of calls, this method should eventually return
	 * an empty list of IDs, indicating that no more IDs are available.
	 *
	 * @since 0.5
	 *
	 * @param int $limit The maximum number of IDs to return.
	 *
	 * @return EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function fetchIds( $limit );
}
