<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for tracking subscriptions of clients to entity
 * change events generated on the repo.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface SubscriptionManager {

	/**
	 * Subscribes the given subscriber to notifications about changes on the specified entities.
	 *
	 * @param string $subscriber Global site ID of the subscriber
	 * @param EntityId[] $entityIds The entities to subscribe to.
	 */
	public function subscribe( string $subscriber, array $entityIds ): void;

	/**
	 * Unsubscribes the given subscriber from notifications about changes on the specified entities.
	 *
	 * @param string $subscriber Global site ID of the subscriber
	 * @param EntityId[] $entityIds The entities to subscribe to.
	 */
	public function unsubscribe( string $subscriber, array $entityIds ): void;

}
