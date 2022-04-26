<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * No-op implementation of the SubscriptionManager and UsageLookup interfaces.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class NullSubscriptionManager implements SubscriptionManager {

	/**
	 * @param string $subscriber
	 * @param EntityId[] $entityIds
	 */
	public function subscribe( string $subscriber, array $entityIds ): void {
		// NO-OP
	}

	/**
	 * @param string $subscriber
	 * @param EntityId[] $entityIds
	 */
	public function unsubscribe( string $subscriber, array $entityIds ): void {
		// NO-OP
	}

}
