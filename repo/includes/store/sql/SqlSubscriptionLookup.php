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
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SqlSubscriptionLookup implements SubscriptionLookup {

	/**
	 * @var LoadBalancer
	 */
	private $dbLoadBalancer;

	/**
	 * @var int
	 */
	private $dbMode;

	/**
	 * @param LoadBalancer $dbLoadBalancer
	 * @param int $dbMode Default is DB_SLAVE
	 */
	public function __construct( LoadBalancer $dbLoadBalancer, $dbMode = DB_SLAVE ) {
		$this->dbLoadBalancer = $dbLoadBalancer;
		$this->dbMode = $dbMode;
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
			return array();
		}

		$dbr = $this->dbLoadBalancer->getConnection( $this->dbMode );

		// NOTE: non-Item ids are ignored, since only items can be subscribed to
		//       via sitelinks.
		$entityIds = $this->reIndexEntityIds( $entityIds );
		$subscribedIds = $this->querySubscriptions( $dbr, $siteId, array_keys( $entityIds ) );

		// collect the item IDs present in these links
		$linkedEntityIds = array();
		foreach ( $subscribedIds as $id ) {
			$linkedEntityIds[$id] = $entityIds[$id];
		}

		$this->dbLoadBalancer->reuseConnection( $dbr );

		return $linkedEntityIds;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return string[] Site IDs
	 */
	public function getSubscribers( array $entityIds ) {
		if ( !empty( $entityIds ) ) {
			$rows = $this->getSubscriberRows( $this->convertEntityIdsToStrings( $entityIds ) );

			if ( $rows ) {
				return $this->convertRowsToSiteIds( $rows );
			}
		}

		return array();
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return string[]
	 */
	private function convertEntityIdsToStrings( array $entityIds ) {
		return array_map(
			function( $entityId ) {
				return $entityId->getSerialization();
			},
			$entityIds
		);
	}

	/**
	 * @param string[] $entityIds
	 *
 	 * @return ResultWrapper|bool
	 */
	private function getSubscriberRows( array $entityIds ) {
		$db = $this->dbLoadBalancer->getConnection( $this->dbMode );

		$rows = $db->select(
			'wb_changes_subscription',
			'cs_subscriber_id',
			array(
				'cs_entity_id' => $entityIds
			),
			__METHOD__
		);

		$this->dbLoadBalancer->reuseConnection( $db );

		return $rows;
	}

	/**
	 * @param ResultWrapper $rows
	 *
	 * @return string[]
	 */
	private function convertRowsToSiteIds( ResultWrapper $rows ) {
        $siteIds = array();                                                                         
                                                                                                    
        foreach ( $rows as $row ) {                                                   
            $siteIds[] = $row->cs_subscriber_id;                                             
        }                                                                                           
                                                                                                    
        return $siteIds;
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
		$reindexed = array();

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
		$values = array();

		foreach ( $rows as $row ) {
			$values[] = $row->$field;
		}

		return $values;
	}

}
