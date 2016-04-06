<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * No-op implementation of the SubscriptionManager and UsageLookup interfaces.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class NullSubscriptionManager implements SubscriptionManager {

	/**
	 * @param string $subscriber
	 * @param EntityId[] $entityIds
	 */
	public function subscribe( $subscriber, array $entityIds ) {
		// NO-OP
	}

	/**
	 * @param string $subscriber
	 * @param EntityId[] $entityIds
	 */
	public function unsubscribe( $subscriber, array $entityIds ) {
		// NO-OP
	}

}
