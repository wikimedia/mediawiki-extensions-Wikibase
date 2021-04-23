<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for looking up an Entity's redirect target id.
 *
 * @since 5.4
 *
 * @license GPL-2.0-or-later
 */
interface EntityRedirectTargetLookup {

	/**
	 * @since 5.4
	 */
	public const FOR_UPDATE = 'for update';

	/**
	 * Returns the redirect target associated with the given redirect ID.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string $forUpdate If EntityRedirectTargetLookup::FOR_UPDATE is given the redirect will be
	 *        determined from the canonical master database.
	 *
	 * @return EntityId|null The ID of the redirect target, or null if $entityId does not refer to a
	 * redirect.
	 * @throws EntityRedirectLookupException
	 */
	public function getRedirectForEntityId( EntityId $entityId, $forUpdate = '' );

}
