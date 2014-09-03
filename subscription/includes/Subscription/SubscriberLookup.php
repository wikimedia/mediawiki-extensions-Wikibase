<?php

namespace Wikibase\Subscription;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityIdPager;

/**
 * Service interface for looking up subscribers to entity change notifications.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface SubscriberLookup {

	/**
	 * Returns the clients subscribed to any of the given entities.
	 *
	 * @param EntityId[] $entities
	 *
	 * @return string[]
	 * @throws SubscriptionException
	 */
	public function getSubscribers( array $entities );

}
