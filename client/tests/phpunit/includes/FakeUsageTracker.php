<?php
namespace Wikibase\Client\Test\Store;

use Iterator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Subscription\SubscriptionException;
use Wikibase\Subscription\SubscriptionManager;
use Wikibase\Usage\UsageLookup;
use Wikibase\Usage\UsageTracker;
use Wikibase\Usage\UsageTrackerException;

class FakeUsageTracker implements UsageTracker, UsageLookup, SubscriptionManager {

	/**
	 * @var array
	 */
	private $usages;

	/**
	 * @var array
	 */
	private $subscriptions;

	public function __construct( array &$usages = array(), array &$subscriptions = array() ) {
		$this->usages = $usages;
		$this->subscriptions = $subscriptions;
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
		if ( !isset( $this->subscriptions[$client] ) ) {
			$this->subscriptions[$client] = array();
		}

		$this->subscriptions[$client] =
			$this->reIndexEntityIds(
				array_merge( $this->subscriptions[$client], $entities )
			);
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
		if ( !isset( $this->subscriptions[$client] ) ) {
			return;
		}

		$this->subscriptions[$client] =
			array_intersect_key(
				$this->subscriptions[$client],
				$this->reIndexEntityIds( $entities )
			);
	}

	/**
	 * Removes all subscriptions for the given client.
	 *
	 * @param string $client global site ID of the client
	 *
	 * @throws SubscriptionException
	 */
	public function removeClient( $client ) {
		unset( $this->subscriptions[$client] );
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
		foreach ( array_keys( $this->subscriptions ) as $client ) {
			$this->unsubscribe( $client, $entities );
		}
	}

	/**
	 * Updates entity usage information for the given page.
	 *
	 * @param int $pageId The ID of the page the entities are used on.
	 * @param array $usages An associative array, mapping aspect identifiers to lists of EntityIds
	 * indicating the entities that are used in the way indicated by that aspect.
	 * Well known aspects are "sitelinks", "label" and "all",
	 * see docs/usagetracking.wiki for details.
	 *
	 * @return array Usages before the update, in the same form as $usages
	 * @throws UsageTrackerException
	 */
	public function trackUsedEntities( $pageId, array $usages ) {
		$this->usages[ $pageId ] = $usages;
	}

	/**
	 * Get the entities used on the given page.
	 *
	 * @param int $pageId
	 *
	 * @return array An associative array mapping aspect identifiers to lists of EntityIds.
	 * @throws UsageTrackerException
	 */
	public function getUsageForPage( $pageId ) {
		return $this->usages[ $pageId ];
	}

	/**
	 * Returns the pages that use any of the given entities.
	 *
	 * @param EntityId[] $entities
	 * @param array $aspects Which aspects to consider (if omitted, all aspects are considered).
	 *
	 * @return Iterator An iterator over the IDs of pages using (the any of the given aspects of)
	 *         any of the given entities
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entities, array $aspects = array() ) {
		$pages = array();
		//TODO //TODO //TODO //TODO //TODO //TODO //TODO //TODO //TODO //TODO
		return $pages;
	}

	/**
	 * Returns the elements of $entities that are currently not used as
	 * far as this UsageTracker knows. In other words, this method answers the
	 * question which of a given list of entities are currently being used on
	 * wiki pages.
	 *
	 * @param EntityId[] $entities
	 *
	 * @return EntityId[] A list of elements of $entities that are unused.
	 * @throws UsageTrackerException
	 */
	public function getUnusedEntities( array $entities ) {
		$unused = $entities;
		//TODO //TODO //TODO //TODO //TODO //TODO //TODO //TODO //TODO //TODO
		return $unused;
	}
}