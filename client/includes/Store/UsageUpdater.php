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
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
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
	 * Adds entity usage information for the given page, and automatically adjusts
	 * any subscriptions based on that usage.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 * @param EntityUsage[] $usages A list of EntityUsage objects.
	 * See @ref docs_topics_usagetracking for details.
	 *
	 * @see UsageTracker::trackUsedEntities
	 *
	 * @throws InvalidArgumentException
	 */
	public function addUsagesForPage( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int!' );
		}
		if ( empty( $usages ) ) {
			return;
		}

		$usageEntityIds = $this->getEntityIds( $usages );
		$newlyUsedEntities = $this->usageLookup->getUnusedEntities( $usageEntityIds );

		$this->usageTracker->addUsedEntities( $pageId, $usages );

		// Subscribe to anything that was unused before.
		if ( !empty( $newlyUsedEntities ) ) {
			$this->subscriptionManager->subscribe( $this->clientId, $newlyUsedEntities );
		}
	}

	/**
	 * Updates entity usage information for the given page, and automatically adjusts
	 * any subscriptions based on that usage.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 * @param EntityUsage[] $usages A list of EntityUsage objects.
	 * See @ref docs_topics_usagetracking for details.
	 *
	 * @see UsageTracker::replaceUsedEntities
	 *
	 * @throws InvalidArgumentException
	 */
	public function replaceUsagesForPage( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int!' );
		}

		$prunedUsages = $this->usageTracker->replaceUsedEntities( $pageId, $usages );
		$currentlyUsedEntities = $this->getEntityIds( $usages );

		// Subscribe to anything that was added
		if ( !empty( $currentlyUsedEntities ) ) {
			$this->subscriptionManager->subscribe( $this->clientId, $currentlyUsedEntities );
		}
		// Unsubscribe from anything that was pruned and is otherwise unused.
		if ( !empty( $prunedUsages ) ) {
			$prunedEntityIds = $this->getEntityIds( $prunedUsages );
			$unusedIds = $this->usageLookup->getUnusedEntities( $prunedEntityIds );
			if ( !empty( $unusedIds ) ) {
				$this->subscriptionManager->unsubscribe( $this->clientId, $unusedIds );
			}
		}
	}

	/**
	 * Removes all usage information for the given page, and removes
	 * any subscriptions that have become unnecessary.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 *
	 * @see UsageTracker::pruneUsages
	 *
	 * @throws InvalidArgumentException
	 */
	public function pruneUsagesForPage( $pageId ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int!' );
		}

		$prunedUsages = $this->usageTracker->pruneUsages( $pageId );

		$prunedEntityIds = $this->getEntityIds( $prunedUsages );
		$unusedIds = $this->usageLookup->getUnusedEntities( $prunedEntityIds );

		if ( !empty( $unusedIds ) ) {
			// Unsubscribe from anything that was pruned and is otherwise unused.
			$this->subscriptionManager->unsubscribe( $this->clientId, $unusedIds );
		}
	}

	/**
	 * @param EntityUsage[] $entityUsages
	 *
	 * @return EntityId[]
	 */
	private function getEntityIds( array $entityUsages ) {
		$entityIds = [];

		foreach ( $entityUsages as $usage ) {
			$id = $usage->getEntityId();
			$key = $id->getSerialization();

			$entityIds[$key] = $id;
		}

		return $entityIds;
	}

}
