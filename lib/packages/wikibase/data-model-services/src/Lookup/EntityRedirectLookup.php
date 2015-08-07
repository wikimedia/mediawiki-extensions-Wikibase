<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for retrieving information about entity redirects.
 *
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityRedirectLookup {

	/**
	 * Returns the IDs that redirect to (are aliases of) the given target entity.
	 *
	 * @since 1.1
	 *
	 * @param EntityId $targetId
	 *
	 * @return EntityId[]
	 */
	public function getRedirectIds( EntityId $targetId );

	/**
	 * Returns the redirect target associated with the given redirect ID.
	 *
	 * @since 1.1
	 *
	 * @param EntityId $entityId
	 * @param string $forUpdate If "for update" is given the redirect will be
	 *        determined from the canonical master database.
	 *
	 * @return EntityId|null|false The ID of the redirect target, or null if $entityId
	 *         does not refer to a redirect, or false if $entityId is not known.
	 */
	public function getRedirectForEntityId( EntityId $entityId, $forUpdate = '' );

}
