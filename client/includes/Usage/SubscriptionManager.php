<?php

namespace Wikibase\Client\Usage;

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

	public function subscribe( $client, $entities ) {
		// DUMMY
	}

	public function unsubscribe( $client, $entities ) {
		// DUMMY
	}

}
