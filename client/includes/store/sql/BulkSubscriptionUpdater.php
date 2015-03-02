<?php

namespace Wikibase\Client\Store\Sql;

use InvalidArgumentException;
use ResultWrapper;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\LogWarningExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;

/**
 * Implements bulk updates for the repo's wb_changes_subscription table,
 * based on the local wbc_entity_usage table. The local wiki will be subscribed to any
 * entity present in that table.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class BulkSubscriptionUpdater {

	/**
	 * @var ConsistentReadConnectionManager
	 */
	private $localConnectionManager;

	/**
	 * @var ConsistentReadConnectionManager
	 */
	private $repoConnectionManager;

	/**
	 * @var string
	 */
	private $subscriberId;

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
	 * @param ConsistentReadConnectionManager $localConnectionManager ConnectionManager for DB connections to the local wiki
	 * @param ConsistentReadConnectionManager $repoConnectionManager ConnectionManager for DB connections to the repo
	 * @param string $subscriberId
	 * @param int $batchSize
	 */
	public function __construct(
		ConsistentReadConnectionManager $localConnectionManager,
		ConsistentReadConnectionManager $repoConnectionManager,
		$subscriberId,
		$batchSize = 1000
	) {
		if ( !is_string( $subscriberId ) ) {
			throw new InvalidArgumentException( '$subscriberId must be a string' );
		}

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		$this->localConnectionManager = $localConnectionManager;
		$this->repoConnectionManager = $repoConnectionManager;

		$this->subscriberId = $subscriberId;
		$this->batchSize = $batchSize;

		$this->exceptionHandler = new LogWarningExceptionHandler();
		$this->progressReporter = new NullMessageReporter();
	}

	/**
	 * @param MessageReporter $progressReporter
	 */
	public function setProgressReporter( MessageReporter $progressReporter ) {
		$this->progressReporter = $progressReporter;
	}

	/**
	 * @return MessageReporter
	 */
	public function getProgressReporter() {
		return $this->progressReporter;
	}

	/**
	 * @param ExceptionHandler $exceptionHandler
	 */
	public function setExceptionHandler( ExceptionHandler $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * @return ExceptionHandler
	 */
	public function getExceptionHandler() {
		return $this->exceptionHandler;
	}

	/**
	 * Insert subscriptions based on entries in wbc_entity_usage.
	 *
	 * @param EntityId $startEntity The entity to start with.
	 */
	public function updateSubscriptions( EntityId $startEntity = null ) {
		$this->repoConnectionManager->forceMaster();

		$continuation = $startEntity === null ? null : array( $startEntity->getSerialization() );

		while ( true ) {
			$count = $this->processUpdateBatch( $continuation );

			if ( $count > 0 ) {
				$this->progressReporter->reportMessage( 'Updating subscription table: '
					. "inserted $count subscriptions, continuing at entity #{$continuation[0]}." );
			} else {
				break;
			}
		};
	}

	/**
	 * @param array &$continuation
	 *
	 * @return int The number of subscriptions inserted.
	 */
	private function processUpdateBatch( &$continuation = array() ) {
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
	private function insertUpdateBatch(  array $entities ) {
		$dbw = $this->repoConnectionManager->beginAtomicSection( __METHOD__ );

		$rows = $this->makeSubscriptionRows( $entities );

		$dbw->insert(
			'wb_changes_subscription',
			$rows,
			__METHOD__,
			array(
				'IGNORE'
			)
		);

		$c = $dbw->affectedRows();
		$this->repoConnectionManager->commitAtomicSection( $dbw, __METHOD__ );

		return $c;
	}

	/**
	 * @param array &$continuation
	 *
	 * @return string[] A list of entity id strings.
	 */
	private function getUpdateBatch( &$continuation = array() ) {
		$dbr = $this->localConnectionManager->getReadConnection();

		if ( empty( $continuation ) ) {
			$continuationCondition = '1';
		} else {
			list( $fromEntityId ) = $continuation;
			$continuationCondition = 'eu_entity_id > ' . $dbr->addQuotes( $fromEntityId );
		}

		$res = $dbr->select(
			'wbc_entity_usage',
			array( 'DISTINCT eu_entity_id' ),
			$continuationCondition,
			__METHOD__,
			array(
				'LIMIT' => $this->batchSize,
				'ORDER BY' => 'eu_entity_id'
			)
		);

		$this->localConnectionManager->releaseConnection( $dbr );
		return $this->slurpSubscriptions( $res, 'eu_entity_id', $continuation );
	}

	/**
	 * @param ResultWrapper $res A result set with the field given by $entityIdField field set for each row.
	 * @param string $entityIdField
	 * @param array &$continuation
	 *
	 * @return string[] An array of entity ID strings.
	 */
	private function slurpSubscriptions( ResultWrapper $res, $entityIdField, &$continuation = array() ) {
		$entities = array();

		foreach ( $res as $row ) {
			$entities[] = $row->$entityIdField;
		}

		if ( isset( $row ) ) {
			$continuation = array( $row->$entityIdField );
		}

		return $entities;
	}

	/**
	 * Returns a list of rows for insertion, using DatabaseBase's multi-row insert mechanism.
	 * Each row is represented as array( $entityId, $subscriber ).
	 *
	 * @param string[] $entities entity id strings
	 *
	 * @return array[] rows
	 */
	private function makeSubscriptionRows( array $entities ) {
		$rows = array();

		foreach ( $entities as $id ) {
			$rows[] = array(
				'cs_entity_id' => $id,
				'cs_subscriber_id' => $this->subscriberId
			);
		}

		return $rows;
	}

	/**
	 * Remove subscriptions for entities not present in in wbc_entity_usage.
	 *
	 * @param EntityId $startEntity The entity to start with.
	 */
	public function purgeSubscriptions( EntityId $startEntity = null ) {
		$continuation = $startEntity === null ? null : array( $startEntity->getSerialization() );

		$this->repoConnectionManager->forceMaster();

		while ( true ) {
			$count = $this->processDeletionBatch( $continuation );

			if ( $count > 0 ) {
				$this->progressReporter->reportMessage( 'Purging subscription table: '
					. "deleted $count subscriptions, continuing at entity #{$continuation[0]}." );
			} else {
				break;
			}
		};
	}

	/**
	 * @param array &$continuation
	 *
	 * @return int The number of subscriptions deleted.
	 */
	private function processDeletionBatch( &$continuation = array() ) {
		$deletionRange = $this->getDeletionRange( $continuation );

		if ( $deletionRange == false ) {
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
	private function getDeletionRange( &$continuation = array() ) {
		$dbr = $this->repoConnectionManager->getReadConnection();

		$conditions = array(
			'cs_subscriber_id' => $this->subscriberId,
		);

		if ( !empty( $continuation ) ) {
			list( $fromEntityId ) = $continuation;
			$conditions[] = 'cs_entity_id > ' . $dbr->addQuotes( $fromEntityId );
		}

		$res = $dbr->select(
			'wb_changes_subscription',
			array( 'cs_entity_id' ),
			$conditions,
			__METHOD__,
			array(
				'LIMIT' => $this->batchSize,
				'ORDER BY' => 'cs_entity_id'
			)
		);

		$this->repoConnectionManager->releaseConnection( $dbr );
		$subscriptions = $this->slurpSubscriptions( $res, 'cs_entity_id', $continuation );

		if ( empty( $subscriptions ) ) {
			return false;
		}

		$minId = reset( $subscriptions );
		$maxId = end( $subscriptions );
		$count = count( $subscriptions );

		return array( $minId, $maxId, $count );
	}

	/**
	 * Deletes a range of subscriptions.
	 *
	 * @param string $minId Entity id string indicating the first element in the deletion range
	 * @param string $maxId Entity id string indicating the last element in the deletion range
	 */
	private function deleteSubscriptionRange( $minId, $maxId ) {
		$dbw = $this->repoConnectionManager->beginAtomicSection( __METHOD__ );

		$conditions = array(
			'cs_subscriber_id' => $this->subscriberId,
			'cs_entity_id >= ' . $dbw->addQuotes( $minId ),
			'cs_entity_id <= ' . $dbw->addQuotes( $maxId ),
		);

		$dbw->delete(
			'wb_changes_subscription',
			$conditions,
			__METHOD__
		);

		$this->repoConnectionManager->commitAtomicSection( $dbw, __METHOD__ );
	}

}
