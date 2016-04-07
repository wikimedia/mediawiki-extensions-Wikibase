<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for retrieving information about entity redirects.
 *
 * @since 1.1
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface EntityRedirectLookup {

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

	/**
	 * Returns the redirect target associated with the given redirect ID.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string $forUpdate If "for update" is given the redirect will be
	 *        determined from the canonical master database.
	 *
	 * @return EntityId|null The ID of the redirect target, or null if $entityId does not refer to a
	 * redirect.
	 * @throws EntityRedirectLookupException
	 */
	public function getRedirectForEntityId( EntityId $entityId, $forUpdate = '' );

}
