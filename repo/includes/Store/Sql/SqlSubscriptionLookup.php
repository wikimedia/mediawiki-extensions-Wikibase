<?php

namespace Wikibase\Store\Sql;

use DatabaseBase;
use LoadBalancer;
use ResultWrapper;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Store\SubscriptionLookup;

/**
 * Implementation of SubscriptionLookup based on a database table.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlSubscriptionLookup implements SubscriptionLookup {

	/**
	 * @var LoadBalancer
	 */
	private $dbLoadBalancer;

	/**
	 * @param LoadBalancer $dbLoadBalancer
	 */
	public function __construct( LoadBalancer $dbLoadBalancer ) {
		$this->dbLoadBalancer = $dbLoadBalancer;
	}

	/**
	 * Returns a list of entities a given site is subscribed to.
	 *
	 * @param string $siteId Site ID of the client site.
	 * @param EntityId[] $entityIds The entities we are interested in, or null for "any".
	 *
	 * @return EntityId[] a list of entity IDs the client wiki is subscribed to.
	 *         The result is limited to entity ids also present in $entityIds, if given.
	 */
	public function getSubscriptions( $siteId, array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return [];
		}

		$dbr = $this->dbLoadBalancer->getConnection( DB_SLAVE );

		// NOTE: non-Item ids are ignored, since only items can be subscribed to
		//       via sitelinks.
		$entityIds = $this->reIndexEntityIds( $entityIds );
		$subscribedIds = $this->querySubscriptions( $dbr, $siteId, array_keys( $entityIds ) );

		// collect the item IDs present in these links
		$linkedEntityIds = [];
		foreach ( $subscribedIds as $id ) {
			$linkedEntityIds[$id] = $entityIds[$id];
		}

		$this->dbLoadBalancer->reuseConnection( $dbr );

		return $linkedEntityIds;
	}

	/**
	 * For a set of potential subscriptions, returns the existing subscriptions.
	 *
	 * @param DatabaseBase $db
	 * @param string $subscriber
	 * @param string[]|null $idsToCheck Id strings to check
	 *
	 * @return string[] Entity ID strings from $subscriptions which $subscriber is subscribed to.
	 */
	private function querySubscriptions( DatabaseBase $db, $subscriber, array $idsToCheck = null ) {
		$where = array(
			'cs_subscriber_id' => $subscriber,
		);

		if ( $idsToCheck ) {
			$where['cs_entity_id'] = $idsToCheck;
		}

		$rows = $db->select(
			'wb_changes_subscription',
			'cs_entity_id',
			$where,
			__METHOD__
		);

		$subscriptions = $this->extractColumn( $rows, 'cs_entity_id' );

		return $subscriptions;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[] The ItemIds from EntityId[], keyed by numeric id.
	 */
	private function reIndexEntityIds( array $entityIds ) {
		$reindexed = [];

		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();
			$reindexed[$key] = $id;
		}

		return $reindexed;
	}

	/**
	 * @param object[]|ResultWrapper $rows Plain objects
	 * @param string $field The name of the field to extract from each plain object
	 *
	 * @return array
	 */
	private function extractColumn( $rows, $field ) {
		$values = [];

		foreach ( $rows as $row ) {
			$values[] = $row->$field;
		}

		return $values;
	}

}
