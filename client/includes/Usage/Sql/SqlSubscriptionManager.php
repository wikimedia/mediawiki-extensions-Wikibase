<?php

namespace Wikibase\Client\Usage\Sql;

use Exception;
use InvalidArgumentException;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageTrackerException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * SubscriptionManager implementation backed by an SQL table.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlSubscriptionManager implements SubscriptionManager {

	/**
	 * @var SessionConsistentConnectionManager
	 */
	private $connectionManager;

	public function __construct( SessionConsistentConnectionManager $connectionManager ) {
		$this->connectionManager = $connectionManager;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return string[]
	 */
	private function idsToString( array $entityIds ) {
		return array_map( function( EntityId $id ) {
			return $id->getSerialization();
		}, $entityIds );
	}

	/**
	 * @see SubscriptionManager::subscribe
	 *
	 * @param string $subscriber
	 * @param EntityId[] $entityIds
	 *
	 * @throws InvalidArgumentException
	 * @throws UsageTrackerException
	 * @throws Exception
	 */
	public function subscribe( $subscriber, array $entityIds ) {
		if ( !is_string( $subscriber ) ) {
			throw new InvalidArgumentException( '$subscriber must be a string.' );
		}

		$subscriptions = $this->idsToString( $entityIds );
		$dbw = $this->connectionManager->getWriteConnectionRef();
		$dbw->startAtomic( __METHOD__ );

		try {
			$oldSubscriptions = $this->querySubscriptions( $dbw, $subscriber, $subscriptions );
			$newSubscriptions = array_diff( $subscriptions, $oldSubscriptions );
			$this->insertSubscriptions( $dbw, $subscriber, $newSubscriptions );

			$dbw->endAtomic( __METHOD__ );
		} catch ( Exception $ex ) {
			$dbw->rollback( __METHOD__ );

			if ( $ex instanceof DBError ) {
				throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
			} else {
				throw $ex;
			}
		}
	}

	/**
	 * @see SubscriptionManager::unsubscribe
	 *
	 * @param string $subscriber Global site ID of the client
	 * @param EntityId[] $entityIds The entities to subscribe to.
	 *
	 * @throws InvalidArgumentException
	 * @throws UsageTrackerException
	 * @throws Exception
	 */
	public function unsubscribe( $subscriber, array $entityIds ) {
		if ( !is_string( $subscriber ) ) {
			throw new InvalidArgumentException( '$subscriber must be a string.' );
		}

		$unsubscriptions = $this->idsToString( $entityIds );
		$dbw = $this->connectionManager->getWriteConnectionRef();
		$dbw->startAtomic( __METHOD__ );

		try {
			$oldSubscriptions = $this->querySubscriptions( $dbw, $subscriber, $unsubscriptions );
			$obsoleteSubscriptions = array_intersect( $unsubscriptions, $oldSubscriptions );
			$this->deleteSubscriptions( $dbw, $subscriber, $obsoleteSubscriptions );

			$dbw->endAtomic( __METHOD__ );
		} catch ( Exception $ex ) {
			$dbw->rollback( __METHOD__ );

			if ( $ex instanceof DBError ) {
				throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
			} else {
				throw $ex;
			}
		}
	}

	/**
	 * For a set of potential subscriptions, returns the existing subscriptions.
	 *
	 * @param IDatabase $db
	 * @param string $subscriber
	 * @param string[] $subscriptions
	 *
	 * @return string[] Entity ID strings from $subscriptions which $subscriber is already subscribed to.
	 */
	private function querySubscriptions( IDatabase $db, $subscriber, array $subscriptions ) {
		if ( $subscriptions ) {
			$subscriptions = $db->selectFieldValues(
				'wb_changes_subscription',
				'cs_entity_id',
				[
					'cs_subscriber_id' => $subscriber,
					'cs_entity_id' => $subscriptions,
				],
				__METHOD__
			);
		}

		return $subscriptions;
	}

	/**
	 * Inserts a set of subscriptions.
	 *
	 * @param IDatabase $db
	 * @param string $subscriber
	 * @param string[] $subscriptions
	 */
	private function insertSubscriptions( IDatabase $db, $subscriber, array $subscriptions ) {
		$rows = $this->makeSubscriptionRows( $subscriber, $subscriptions );

		$db->insert(
			'wb_changes_subscription',
			$rows,
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * Inserts a set of subscriptions.
	 *
	 * @param IDatabase $db
	 * @param string $subscriber
	 * @param string[] $subscriptions
	 */
	private function deleteSubscriptions( IDatabase $db, $subscriber, array $subscriptions ) {
		if ( $subscriptions ) {
			$db->delete(
				'wb_changes_subscription',
				[
					'cs_subscriber_id' => $subscriber,
					'cs_entity_id' => $subscriptions,
				],
				__METHOD__
			);
		}
	}

	/**
	 * Returns a list of rows for insertion, using IDatabase's multi-row insert mechanism.
	 * Each row is represented as array( $subscriber, $entityId ).
	 *
	 * @param string $subscriber
	 * @param string[] $subscriptions
	 *
	 * @return array[] rows
	 */
	private function makeSubscriptionRows( $subscriber, array $subscriptions ) {
		$rows = [];

		foreach ( $subscriptions as $entityId ) {
			$rows[] = [
				'cs_entity_id' => $entityId,
				'cs_subscriber_id' => $subscriber
			];
		}

		return $rows;
	}

}
