<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for looking up which client is interested in changes to which entity.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface SubscriptionLookup {

	/**
	 * Return the existing subscriptions for given Id to check
	 *
	 * @param EntityId $idToCheck EntityId to get subscribers
	 *
	 * @return string[] wiki IDs of wikis subscribed to the given entity
	 */
	public function getSubscribers( EntityId $idToCheck );

}
