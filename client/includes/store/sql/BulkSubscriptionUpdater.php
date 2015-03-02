<?php

namespace Wikibase\Client\Store\Sql;

use InvalidArgumentException;
use LoadBalancer;
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
	 * @var LoadBalancer
	 */
	private $localLoadBalancer;

	/**
	 * @var LoadBalancer
	 */
	private $repoLoadBalancer;

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
	 * @param LoadBalancer $localLoadBalancer LoadBalancer for DB connections to the local wiki
	 * @param LoadBalancer $repoLoadBalancer LoadBalancer for DB connections to the repo
	 * @param string $subscriberId
	 * @param int $batchSize
	 */
	public function __construct( LoadBalancer $localLoadBalancer, LoadBalancer $repoLoadBalancer, $subscriberId, $batchSize = 1000 ) {
		if ( !is_string( $subscriberId ) ) {
			throw new InvalidArgumentException( '$subscriberId must be a string' );
		}

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		$this->localLoadBalancer = $localLoadBalancer;
		$this->repoLoadBalancer = $repoLoadBalancer;

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
		$continuation = $startEntity === null ? null : array( $startEntity->getSerialization(), '' );

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
		$entityPerPage = $this->getUpdateBatch($continuation );

		if ( empty( $entityPerPage ) ) {
			return 0;
		}

		$count = $this->insertUpdateBatch( $entityPerPage );
		return $count;
	}

	/**
	 * @param string[] $entities Entity-IDs to subscribe to
	 *
	 * @return int The number of rows inserted.
	 */
	private function insertUpdateBatch(  array $entities ) {
		$dbw = $this->repoLoadBalancer->getConnection( DB_MASTER );

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
		$this->repoLoadBalancer->reuseConnection( $dbw );

		return $c;
	}

	/**
	 * @param array &$continuation
	 *
	 * @return array[] An associative array mapping entity IDs to lists of site IDs.
	 */
	private function getUpdateBatch( &$continuation = array() ) {
		$dbr = $this->localLoadBalancer->getConnection( DB_MASTER );

		if ( empty( $continuation ) ) {
			$continuationCondition = '1';
		} else {
			list( $fromEntityId ) = $continuation;
			$continuationCondition = 'eu_entity_id > ' . $dbr->addQuotes( $fromEntityId );
		}

		$res = $dbr->select(
			'wbc_entity_usage',
			array( 'UNIQUE eu_entity_id' ),
			$continuationCondition,
			__METHOD__,
			array(
				'LIMIT' => $this->batchSize,
				'ORDER BY eu_entity_id'
			)
		);

		$this->localLoadBalancer->reuseConnection( $dbr );
		return $this->slurpSubscriptions( $res, $continuation );
	}

	/**
	 * @param ResultWrapper $res A result set with the eu_entity_id field set for each row.
	 * @param array &$continuation
	 *
	 * @return string[] An array of entity ID strings.
	 */
	private function slurpSubscriptions( ResultWrapper $res, &$continuation = array() ) {
		$entities = array();

		foreach ( $res as $row ) {
			$entities[] = $row->eu_entity_id;
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
	public function purgeSubscriptions() {
		while ( true ) {
			$count = $this->runDeletionBatch();

			if ( $count > 0 ) {
				$this->progressReporter->reportMessage( 'Purging subscription table: '
					. "deleted $count subscriptions for {$this->subscriberId}." );
			} else {
				break;
			}
		};
	}

	/**
	 * Deletes a batch of subscriptions.
	 *
	 * @return int The number of rows deleted.
	 */
	private function runDeletionBatch() {
		$dbw = $this->repoLoadBalancer->getConnection( DB_MASTER );

		$dbw->delete(
			'wb_changes_subscription',
			array(
				'cs_subscriber_id' => $this->subscriberId
			),
			__METHOD__,
			array(
				'LIMIT' => $this->batchSize,
			)
		);

		$c = $dbw->affectedRows();
		$this->repoLoadBalancer->reuseConnection( $dbw );

		return $c;
	}

}
