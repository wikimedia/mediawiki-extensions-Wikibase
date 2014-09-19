<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for tracking subscriptions of clients to entity
 * change events generated on the repo.
 *
 * @note This is a dummy implementation, it will be replaced by an interface
 * and implementation in a separate component.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SubscriptionManager {

	/**
	 * Subscribes the given client to notificatiosn about changes on the specified entities.
	 *
	 * @param string $client Global site ID of the client
	 * @param EntityId[] $entityIds The entities to subscribe to.
	 */
	public function subscribe( $client, array $entityIds ) {
		// NO-OP
	}

	/**
	 * Unsubscribes the given client from notificatiosn about changes on the specified entities.
	 *
	 * @param string $client Global site ID of the client
	 * @param EntityId[] $entityIds The entities to subscribe to.
	 */
	public function unsubscribe( $client, array $entities ) {
		// NO-OP
	}

}
