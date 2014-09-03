<?php

namespace Wikibase\Subscription;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for managing subscriptions to entity change notifications.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface SubscriptionManager {

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
	public function subscribe( $client, array $entities, $filter  = array() );

	/**
	 * Unsubscribes the given client from notifications about the given events.
	 *
	 * @param string $client global site ID of the client
	 * @param EntityId[] $entities
	 *
	 * @throws SubscriptionException
	 */
	public function unsubscribe( $client, array $entities );

	/**
	 * Removes all subscriptions for the given client.
	 *
	 * @param string $client global site ID of the client
	 *
	 * @throws SubscriptionException
	 */
	public function removeClient( $client );

	/**
	 * Removes all subscriptions for the given set of entities.
	 * This is used typically when entities were deleted.
	 *
	 * @param EntityId[] $entities
	 *
	 * @throws SubscriptionException
	 */
	public function removeEntities( array $entities );

}
