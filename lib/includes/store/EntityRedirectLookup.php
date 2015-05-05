<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for retrieving information about entity redirects.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityRedirectLookup {

	/**
	 * Returns the IDs that redirect to (are aliases of) the given target entity.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $targetId
	 *
	 * @return EntityId[]
	 */
	public function getRedirectIds( EntityId $targetId );

}
