<?php

namespace Wikibase\Subscription;

use Wikibase\DataModel\Entity\EntityId;

/**
 * An SQL based subscription manager implementation.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlSubscriptionManager implements SubscriptionManager, SubscriberLookup {

	/**
	 * Returns the clients subscribed to any of the given entities.
	 *
	 * @param EntityId[] $entities
	 *
	 * @return string[]
	 * @throws SubscriptionException
	 */
	public function getSubscribers( array $entities ) {
		// TODO: Implement getSubscribers() method.
		return array();
	}

	/**
	 * Subscribes the given client to notifications about the given events.
	 *
	 * @param string $client global site ID of the client
	 * @param EntityId[] $entities
	 * @param array $filter An associative array of filter options. The filter is advisory:
	 * the event source may or may not support and regard them.
	 *
	 * @throws SubscriptionException
	 */
	public function subscribe( $client, array $entities, $filter = array() ) {
		// TODO: Implement subscribe() method.
	}

	/**
	 * Unsubscribes the given client from notifications about the given events.
	 *
	 * @param string $client global site ID of the client
	 * @param EntityId[] $entities
	 *
	 * @throws SubscriptionException
	 */
	public function unsubscribe( $client, array $entities ) {
		// TODO: Implement unsubscribe() method.
	}

	/**
	 * Removes all subscriptions for the given client.
	 *
	 * @param string $client global site ID of the client
	 *
	 * @throws SubscriptionException
	 */
	public function removeClient( $client ) {
		// TODO: Implement removeClient() method.
	}

	/**
	 * Removes all subscriptions for the given set of entities.
	 * This is used typically when entities were deleted.
	 *
	 * @param EntityId[] $entities
	 *
	 * @throws SubscriptionException
	 */
	public function removeEntities( array $entities ) {
		// TODO: Implement removeEntities() method.
	}
}
 