<?php

namespace Wikibase\Client\Store\Sql;

use InvalidArgumentException;
use Wikibase\Client\Usage\Sql\EntityUsageTable;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\LogWarningExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;

/**
 * Implements bulk updates for the repo's wb_changes_subscription table,
 * based on the client's local wbc_entity_usage table. The client wiki will be subscribed
 * to be informed about changes to any entity present in the local wbc_entity_usage table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class BulkSubscriptionUpdater {

	/**
	 * @var SessionConsistentConnectionManager
	 */
	private $localConnectionManager;

	/**
	 * @var SessionConsistentConnectionManager
	 */
	private $repoConnectionManager;

	/**
	 * @var string The local wiki's global ID, to be used as the subscriber ID in the repo's subecription table.
	 */
	private $subscriberWikiId;

	/**
	 * @var string|false The repo wiki's id, as used by the LoadBalancer. Used for wait for replicas.
	 *                   False indicates to use the local wiki's database, and is the default
	 *                   for the repoWiki setting.
	 */
	private $repoWiki;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var ExceptionHandler
	 */
	private $exceptionHandler;

	/**
	 * @var MessageReporter
	 */
	private $progressReporter;

	/**
	 * @param SessionConsistentConnectionManager $localConnectionManager Connection manager for DB
	 * connections to the local wiki.
	 * @param SessionConsistentConnectionManager $repoConnectionManager Connection manager for DB
	 * connections to the repo.
	 * @param string $subscriberWikiId The local wiki's global ID, to be used as the subscriber ID
	 * in the repo's subscription table.
	 * @param string|false $repoWiki The repo wiki's id, as used by the LoadBalancer.
	 *                               False (default of the repoWiki setting) indicates to
	 *                               use local wiki database.
	 * @param int $batchSize
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		SessionConsistentConnectionManager $localConnectionManager,
		SessionConsistentConnectionManager $repoConnectionManager,
		$subscriberWikiId,
		$repoWiki,
		$batchSize = 1000
	) {
		if ( !is_string( $subscriberWikiId ) ) {
			throw new InvalidArgumentException( '$subscriberWikiId must be a string' );
		}

		if ( !is_string( $repoWiki ) && $repoWiki !== false ) {
			throw new InvalidArgumentException( '$repoWiki must be a string or false' );
		}

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		$this->localConnectionManager = $localConnectionManager;
		$this->repoConnectionManager = $repoConnectionManager;

		$this->subscriberWikiId = $subscriberWikiId;
		$this->repoWiki = $repoWiki;
		$this->batchSize = $batchSize;

		$this->exceptionHandler = new LogWarningExceptionHandler();
		$this->progressReporter = new NullMessageReporter();
	}

	public function setProgressReporter( MessageReporter $progressReporter ) {
		$this->progressReporter = $progressReporter;
	}

	public function setExceptionHandler( ExceptionHandler $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Insert subscriptions based on entries in wbc_entity_usage.
	 *
	 * @param EntityId|null $startEntity The entity to start with.
	 */
	public function updateSubscriptions( EntityId $startEntity = null ) {
		$this->repoConnectionManager->prepareForUpdates();

		$continuation = $startEntity === null ? null : [ $startEntity->getSerialization() ];

		while ( true ) {
			wfWaitForSlaves( null, $this->repoWiki );

			$count = $this->processUpdateBatch( $continuation );

			if ( $count > 0 ) {
				$this->progressReporter->reportMessage( 'Updating subscription table: '
					. "inserted $count subscriptions, continuing at entity #{$continuation[0]}." );
			} else {
				break;
			}
		}
	}

	/**
	 * @param array &$continuation
	 *
	 * @return int The number of subscriptions inserted.
	 */
	private function processUpdateBatch( array &$continuation = null ) {
		$entityIds = $this->getUpdateBatch( $continuation );

		if ( empty( $entityIds ) ) {
			return 0;
		}

		$count = $this->insertUpdateBatch( $entityIds );
		return $count;
	}

	/**
	 * @param string[] $entities Entity-IDs to subscribe to
	 *
	 * @return int The number of rows inserted.
	 */
	private function insertUpdateBatch( array $entities ) {
		$dbw = $this->repoConnectionManager->getWriteConnectionRef();
		$dbw->startAtomic( __METHOD__ );

		$rows = $this->makeSubscriptionRows( $entities );

		$dbw->insert(
			'wb_changes_subscription',
			$rows,
			__METHOD__,
			[
				'IGNORE'
			]
		);

		$count = $dbw->affectedRows();
		$dbw->endAtomic( __METHOD__ );

		return $count;
	}

	/**
	 * @param array &$continuation
	 *
	 * @return string[] A list of entity id strings.
	 */
	private function getUpdateBatch( array &$continuation = null ) {
		$dbr = $this->localConnectionManager->getReadConnection();

		if ( empty( $continuation ) ) {
			$continuationCondition = '1';
		} else {
			list( $fromEntityId ) = $continuation;
			$continuationCondition = 'eu_entity_id > ' . $dbr->addQuotes( $fromEntityId );
		}

		$res = $dbr->select(
			EntityUsageTable::DEFAULT_TABLE_NAME,
			[ 'DISTINCT eu_entity_id' ],
			$continuationCondition,
			__METHOD__,
			[
				'ORDER BY' => 'eu_entity_id',
				'LIMIT' => $this->batchSize,
			]
		);

		$this->localConnectionManager->releaseConnection( $dbr );
		return $this->getEntityIdsFromRows( $res, 'eu_entity_id', $continuation );
	}

	/**
	 * Returns a list of rows for insertion, using DatabaseBase's multi-row insert mechanism.
	 * Each row is represented as [ $entityId, $subscriber ].
	 *
	 * @param string[] $entities entity id strings
	 *
	 * @return array[] rows
	 */
	private function makeSubscriptionRows( array $entities ) {
		$rows = [];

		foreach ( $entities as $id ) {
			$rows[] = [
				'cs_entity_id' => $id,
				'cs_subscriber_id' => $this->subscriberWikiId
			];
		}

		return $rows;
	}

	/**
	 * Extracts entity id strings from the rows in a query result, and updates $continuation
	 * to a position "after" the content of the given query result.
	 *
	 * @param IResultWrapper $res A result set with the field given by $entityIdField field set for each row.
	 *        The result is expected to be sorted by entity id, in ascending order.
	 * @param string $entityIdField The name of the field that contains the entity id.
	 * @param array &$continuation Updated to an array containing the last EntityId in the result.
	 *
	 * @return string[] A list of entity ids strings.
	 */
	private function getEntityIdsFromRows( IResultWrapper $res, $entityIdField, array &$continuation = null ) {
		$entities = [];

		foreach ( $res as $row ) {
			$entities[] = $row->$entityIdField;
		}

		if ( isset( $row ) ) {
			$continuation = [ $row->$entityIdField ];
		}

		return $entities;
	}

	/**
	 * Remove subscriptions for entities not present in in wbc_entity_usage.
	 *
	 * @param EntityId|null $startEntity The entity to start with.
	 */
	public function purgeSubscriptions( EntityId $startEntity = null ) {
		$continuation = $startEntity === null ? null : [ $startEntity->getSerialization() ];

		$this->repoConnectionManager->prepareForUpdates();

		while ( true ) {
			wfWaitForSlaves( null, $this->repoWiki );

			$count = $this->processDeletionBatch( $continuation );

			if ( $count > 0 ) {
				$this->progressReporter->reportMessage( 'Purging subscription table: '
					. "deleted $count subscriptions, continuing at entity #{$continuation[0]}." );
			} else {
				break;
			}
		}
	}

	/**
	 * @param array &$continuation
	 *
	 * @return int The number of subscriptions deleted.
	 */
	private function processDeletionBatch( array &$continuation = null ) {
		$deletionRange = $this->getDeletionRange( $continuation );

		if ( $deletionRange === false ) {
			return 0;
		}

		list( $minId, $maxId, $count ) = $deletionRange;
		$this->deleteSubscriptionRange( $minId, $maxId );

		return $count;
	}

	/**
	 * Returns a range of entity IDs to delete, based on this updater's batch size.
	 *
	 * @param array &$continuation
	 *
	 * @return bool|string[] list( $minId, $maxId, $count ), or false if there is nothing to delete
	 */
	private function getDeletionRange( array &$continuation = null ) {
		$dbr = $this->repoConnectionManager->getReadConnection();

		$conditions = [
			'cs_subscriber_id' => $this->subscriberWikiId,
		];

		if ( !empty( $continuation ) ) {
			list( $fromEntityId ) = $continuation;
			$conditions[] = 'cs_entity_id > ' . $dbr->addQuotes( $fromEntityId );
		}

		/**
		 * @note Below, we query and iterate all rows we want to delete in the current batch. That
		 * is rather ugly, but appears to be the best solution, because:
		 *
		 * - Deletions must be paged to avoid lock retention.
		 * - DELETE does not support LIMIT, so we need to know a range (min/max) of IDs.
		 * - GROUP BY does not support LIMIT, so we cannot use aggregate functions to get the
		 *   min/max IDs.
		 *
		 * Thus, using SELECT ... LIMIT seems to be the only reliable way to get the min/max range
		 * needed for batched deletion.
		 */

		$res = $dbr->select(
			'wb_changes_subscription',
			[ 'cs_entity_id' ],
			$conditions,
			__METHOD__,
			[
				'ORDER BY' => 'cs_entity_id',
				'LIMIT' => $this->batchSize,
			]
		);

		$this->repoConnectionManager->releaseConnection( $dbr );
		$subscriptions = $this->getEntityIdsFromRows( $res, 'cs_entity_id', $continuation );

		if ( empty( $subscriptions ) ) {
			return false;
		}

		$minId = reset( $subscriptions );
		$maxId = end( $subscriptions );
		$count = count( $subscriptions );

		return [ $minId, $maxId, $count ];
	}

	/**
	 * Deletes a range of subscriptions.
	 *
	 * @param string $minId Entity id string indicating the first element in the deletion range
	 * @param string $maxId Entity id string indicating the last element in the deletion range
	 */
	private function deleteSubscriptionRange( $minId, $maxId ) {
		$dbw = $this->repoConnectionManager->getWriteConnectionRef();
		$dbw->startAtomic( __METHOD__ );

		$conditions = [
			'cs_subscriber_id' => $this->subscriberWikiId,
			'cs_entity_id >= ' . $dbw->addQuotes( $minId ),
			'cs_entity_id <= ' . $dbw->addQuotes( $maxId ),
		];

		$dbw->delete(
			'wb_changes_subscription',
			$conditions,
			__METHOD__
		);

		$dbw->endAtomic( __METHOD__ );
	}

}
