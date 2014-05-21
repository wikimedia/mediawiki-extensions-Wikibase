<?php

namespace Wikibase;

use Title;

/**
 * Represents a mapping from wiki page titles to entity IDs.
 * The mapping could be programmatic, or it could be based on database lookups.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Michał Łazowik
 */
interface EntityIdLookup {

	/**
	 * Returns the EntityId for a given Title or null if the Title does not
	 * represent an Entity.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 *
	 * @return EntityId|null
	 */
	public function getIdForTitle( Title $title );

}
