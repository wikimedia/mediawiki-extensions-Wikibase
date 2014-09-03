<?php

namespace Wikibase\Client\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Subscription\SubscriptionManager;
use Wikibase\Usage\UsageLookup;
use Wikibase\Usage\UsageTracker;
use Wikibase\Usage\UsageTrackerException;

/**
 * Service for updating usage tracking and associated change subscription information.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageUpdater {

	/**
	 * @var string $clientId
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
	 * @param array $usages An associative array, mapping aspect identifiers to lists of EntityIds
	 * indicating the entities that are used in the way indicated by that aspect.
	 * Well known aspects are "sitelinks", "label" and "all",
	 * see docs/usagetracking.wiki for details.
	 *
	 * @see UsageTracker::trackUsedEntities
	 *
	 * @throws UsageTrackerException
	 */
	public function updateUsageForPage( $pageId, array $usages ) {
		$oldUsage = $this->usageTracker->trackUsedEntities( $pageId, $usages );

		list( $added, $removed ) = $this->getUsageDifference( $oldUsage, $usages );

		if ( empty( $added ) && empty( $removed ) ) {
			return;
		}

		$unused =  $this->usageLookup->getUnusedEntities( $removed );

		if ( empty( $added ) && empty( $unused ) ) {
			return;
		}

		// Subscribe to anything that was added, unsubscribe from anything
		// that was removed and is otherwise unused.
		$this->subscriptionManager->subscribe( $this->clientId, $added );
		$this->subscriptionManager->unsubscribe( $this->clientId, $unused );
	}

	/**
	 * @param array[] $arrays
	 *
	 * @return array
	 */
	private function flatten( array $arrays ) {
		$list = array();

		foreach ( $arrays as $array ) {
			$list = array_merge( $list, $array );
		}

		return $list;
	}

	/**
	 * Re-indexes the given list of EntityIds so that each EntityId can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityId[] $ids
	 * @return EntityId[]
	 */
	private function reindexEntityIds( $ids ) {
		$reindexed = array();

		foreach ( $ids as $id ) {
			$key = $id->getSerialization();
			$reindexed[$key] = $id;
		}

		return $reindexed;
	}

	/**
	 * @param array[] $old
	 * @param array[] $new
	 *
	 * @return array list( $added, $removed )
	 */
	private function getUsageDifference( array $old, array $new ) {
		$old = $this->flatten( $old );
		$new = $this->flatten( $new );

		if ( !empty( $old ) && isset( $old[0] ) ) {
			$old = $this->reindexEntityIds( $old );
		}

		if ( !empty( $new ) && isset( $new[0] ) ) {
			$new = $this->reindexEntityIds( $new );
		}

		$added = array_diff_key( $new, $old );
		$removed = array_diff_key( $old, $new );

		return array( $added, $removed );
	}

}
 