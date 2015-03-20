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
	 * Returns the subscribers for a given set of entities.
	 *
	 * @param EntityId[] $entityIds The entities we are interested in
	 *
	 * @return string[] a list of subscriber IDs (global site IDs as used by Site objects)
	 */
	//public function getSubscribers( array $entityIds );

	/**
	 * Returns a list of entities a given site is subscribed to.
	 *
	 * @todo: make $entityIds optional to return all
	 *
	 * @param string $siteId Site ID of the client site.
	 * @param EntityId[] $entityIds The entities we are interested in.
	 *
	 * @return EntityId[] a list of entity IDs the client wiki is subscribed to.
	 *         The result is limited to entity ids also present in $entityIds, if given.
	 */
	public function getSubscriptions( $siteId, array $entityIds );

}
