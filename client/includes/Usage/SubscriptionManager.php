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

	/**
	 * @param string $client
	 * @param array $entityIds
	 *
	 * @throws UsageTrackerException
	 */
	public function subscribe( $client, array $entityIds ) {
		// DUMMY
	}

	/**
	 * @param string $client
	 * @param array $entityIds
	 *
	 * @throws UsageTrackerException
	 */
	public function unsubscribe( $client, array $entityIds ) {
		// DUMMY
	}

}
