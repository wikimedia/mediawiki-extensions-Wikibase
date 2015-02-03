<?php

namespace Wikibase\Client\Store;

use InvalidArgumentException;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service for updating usage tracking and associated change subscription information.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageUpdater {

	/**
	 * @var string
	 */
	private $clientId;

	/**
	 * @var UsageTracker
	 */
	private $usageTracker;

	/**
	 * @var UsageLookup
	 */
	private $usageLookup;

	/**
	 * @var SubscriptionManager
	 */
	private $subscriptionManager;

	/**
	 * @param string $clientId
	 * @param UsageTracker $usageTracker
	 * @param UsageLookup $usageLookup
	 * @param SubscriptionManager $subscriptionManager
	 */
	public function __construct(
		$clientId,
		UsageTracker $usageTracker,
		UsageLookup $usageLookup,
		SubscriptionManager $subscriptionManager
	) {

		$this->clientId = $clientId;
		$this->usageTracker = $usageTracker;
		$this->usageLookup = $usageLookup;
		$this->subscriptionManager = $subscriptionManager;
	}

	/**
	 * Updates entity usage information for the given page, and automatically adjusts
	 * any subscriptions based on that usage.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 * @param EntityUsage[] $usages A list of EntityUsage objects.
	 * See docs/usagetracking.wiki for details.
	 *
	 * @throws InvalidArgumentException
	 * @see UsageTracker::trackUsedEntities
	 */
	public function updateUsageForPage( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int!' );
		}

		$oldUsage = $this->usageTracker->trackUsedEntities( $pageId, $usages );

		$currentlyUsedEntities = $this->getEntityIds( $usages );
		$previouslyUsedEntities = $this->getEntityIds( $oldUsage );

		$added = array_diff_key( $currentlyUsedEntities, $previouslyUsedEntities );
		$removed = array_diff_key( $previouslyUsedEntities, $currentlyUsedEntities );

		// Subscribe to anything that was added
		if ( !empty( $added ) ) {
			$this->subscriptionManager->subscribe( $this->clientId, $added );
		}

		$this->unsubscribeUnused( $removed );
	}

	/**
	 * Unsubscribe from anything that was removed and is otherwise unused.
	 *
	 * @param EntityId[] $removedIds
	 */
	private function unsubscribeUnused( array $removedIds ) {
		if ( !empty( $removedIds ) ) {
			$unusedIds =  $this->usageLookup->getUnusedEntities( $removedIds );

			if ( !empty( $unusedIds ) ) {
				// Unsubscribe from anything that was removed and is otherwise unused.
				$this->subscriptionManager->unsubscribe( $this->clientId, $unusedIds );
			}
		}
	}

	/**
	 * @param EntityUsage[] $entityUsages
	 *
	 * @return EntityId[]
	 */
	private function getEntityIds( array $entityUsages ) {
		$entityIds = array();

		foreach ( $entityUsages as $usage ) {
			$id = $usage->getEntityId();
			$key = $id->getSerialization();

			$entityIds[$key] = $id;
		}

		return $entityIds;
	}

}
