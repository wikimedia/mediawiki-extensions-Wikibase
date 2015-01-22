<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * No-op implementation of the SubscriptionManager and UsageLookup interfaces.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NullSubscriptionManager implements SubscriptionManager {

	/**
	 * Subscribes the given subscriber to notifications about changes on the specified entities.
	 *
	 * @param string $subscriber Global site ID of the subscriber
	 * @param EntityId[] $entityIds The entities to subscribe to.
	 *
	 * @throws UsageTrackerException
	 */
	public function subscribe( $subscriber, array $entityIds ) {
		// NO-OP
	}

	/**
	 * Unsubscribes the given subscriber from notifications about changes on the specified entities.
	 *
	 * @param string $subscriber Global site ID of the subscriber
	 * @param EntityId[] $entityIds The entities to subscribe to.
	 *
	 * @throws UsageTrackerException
	 */
	public function unsubscribe( $subscriber, array $entityIds ) {
		// NO-OP
	}
}
