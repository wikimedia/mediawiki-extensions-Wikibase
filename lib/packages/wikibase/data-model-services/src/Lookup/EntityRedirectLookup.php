<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for retrieving information about entity redirects.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface EntityRedirectLookup extends EntityRedirectTargetLookup {

	/**
	 * Returns the IDs of the entities that redirect to (are aliases of) the given target entity.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $targetId
	 *
	 * @return EntityId[]
	 * @throws EntityRedirectLookupException
	 */
	public function getRedirectIds( EntityId $targetId );

}
