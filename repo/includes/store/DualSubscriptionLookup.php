<?php

namespace Wikibase\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Implementation of SubscriptionLookup based on two other SubscriptionLookup.
 * This provides a unified view on two subscription mechanisms.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DualSubscriptionLookup implements SubscriptionLookup {

	/**
	 * @var SubscriptionLookup
	 */
	private $primary;

	/**
	 * @var SubscriptionLookup
	 */
	private $secondary;

	/**
	 * @param SubscriptionLookup $primary
	 * @param SubscriptionLookup $secondary
	 */
	public function __construct( SubscriptionLookup $primary, SubscriptionLookup $secondary ) {
		$this->primary = $primary;
		$this->secondary = $secondary;
	}

	/**
	 * Returns a list of entities a given site is subscribed to.
	 *
	 * This implementations combines the results of queries to both of the lookups
	 * provided to the constructor.
	 *
	 * @param string $siteId Site ID of the client site.
	 * @param EntityId[]|null $entityIds The entities we are interested in, or null for "any".
	 *
	 * @return EntityId[] a list of entity IDs the client wiki is subscribed to.
	 *         The result is limited to entity ids also present in $entityIds, if given.
	 */
	public function getSubscriptions( $siteId, array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		$primarySubscriptions = $this->reIndexEntityIds(
			$this->primary->getSubscriptions( $siteId, $entityIds )
		);

		$entityIds = array_diff( $this->reIndexEntityIds( $entityIds ), $primarySubscriptions );

		if ( empty( $entityIds ) ) {
			return $primarySubscriptions;
		}

		$secondarySubscriptions = $this->reIndexEntityIds(
			$this->secondary->getSubscriptions( $siteId, $entityIds )
		);

		$subscriptions = array_merge( $primarySubscriptions, $secondarySubscriptions );
		return $subscriptions;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return ItemId[] The ItemIds from EntityId[], keyed by numeric id.
	 */
	private function reIndexEntityIds( array $entityIds ) {
		$reindexed = array();

		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();
			$reindexed[$key] = $id;
		}

		return $reindexed;
	}

}
