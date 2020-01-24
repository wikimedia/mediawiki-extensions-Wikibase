<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A cursor for paging through EntityIds.
 *
 * @since 3.7
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface EntityIdPager {

	/**
	 * Omit redirects from entity listing.
	 */
	public const NO_REDIRECTS = 'no';

	/**
	 * Include redirects in entity listing.
	 */
	public const INCLUDE_REDIRECTS = 'include';

	/**
	 * Include only redirects in listing.
	 */
	public const ONLY_REDIRECTS = 'only';

	/**
	 * Fetches the next batch of IDs. Calling this has the side effect of advancing the
	 * internal state of the page, typically implemented by some underlying resource
	 * such as a file pointer or a database connection.
	 *
	 * @note After some finite number of calls, this method should eventually return
	 * an empty list of IDs, indicating that no more IDs are available.
	 *
	 * @since 3.7
	 *
	 * @param int $limit The maximum number of IDs to return.
	 *
	 * @return EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function fetchIds( $limit );

}
