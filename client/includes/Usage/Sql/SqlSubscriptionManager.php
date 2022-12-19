<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage\Sql;

use Exception;
use InvalidArgumentException;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;

/**
 * SubscriptionManager implementation backed by an SQL table.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL-2.0-or-later
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
	private function idsToString( array $entityIds ): array {
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
	 * @throws Exception
	 */
	public function subscribe( string $subscriber, array $entityIds ): void {
		$subscriptions = $this->idsToString( $entityIds );
		$dbw = $this->connectionManager->getWriteConnection();
		$dbw->startAtomic( __METHOD__ );
		$oldSubscriptions = $this->querySubscriptions( $dbw, $subscriber, $subscriptions );
		$newSubscriptions = array_diff( $subscriptions, $oldSubscriptions );
		$this->insertSubscriptions( $dbw, $subscriber, $newSubscriptions );
		$dbw->endAtomic( __METHOD__ );
	}

	/**
	 * @see SubscriptionManager::unsubscribe
	 *
	 * @param string $subscriber Global site ID of the client
	 * @param EntityId[] $entityIds The entities to subscribe to.
	 *
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function unsubscribe( string $subscriber, array $entityIds ): void {
		$unsubscriptions = $this->idsToString( $entityIds );
		$dbw = $this->connectionManager->getWriteConnection();
		$dbw->startAtomic( __METHOD__ );
		$oldSubscriptions = $this->querySubscriptions( $dbw, $subscriber, $unsubscriptions );
		$obsoleteSubscriptions = array_intersect( $unsubscriptions, $oldSubscriptions );
		$this->deleteSubscriptions( $dbw, $subscriber, $obsoleteSubscriptions );
		$dbw->endAtomic( __METHOD__ );
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
	private function querySubscriptions( IDatabase $db, string $subscriber, array $subscriptions ): array {
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
	private function insertSubscriptions( IDatabase $db, string $subscriber, array $subscriptions ): void {
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
	private function deleteSubscriptions( IDatabase $db, string $subscriber, array $subscriptions ): void {
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
	 * Each row is represented as [ $subscriber, $entityId ].
	 *
	 * @param string $subscriber
	 * @param string[] $subscriptions
	 *
	 * @return array[] rows
	 */
	private function makeSubscriptionRows( string $subscriber, array $subscriptions ): array {
		$rows = [];

		foreach ( $subscriptions as $entityId ) {
			$rows[] = [
				'cs_entity_id' => $entityId,
				'cs_subscriber_id' => $subscriber,
			];
		}

		return $rows;
	}

}
