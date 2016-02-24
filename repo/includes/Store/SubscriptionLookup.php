<?php

namespace Wikibase\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for looking up which client is interested in changes to which entity.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface SubscriptionLookup {

	/**
	 * Filters a list of EntityIDs, returning the ones that the given client site is
	 * subscribed to.
	 *
	 * @param string $siteId Site ID of the client site.
	 * @param EntityId[] $entityIds The entities we are interested in.
	 *
	 * @return EntityId[] a list of entity IDs the client wiki is subscribed to.
	 *         The result is limited to entity ids also present in $entityIds, if given.
	 */
	public function getSubscriptions( $siteId, array $entityIds );

}
