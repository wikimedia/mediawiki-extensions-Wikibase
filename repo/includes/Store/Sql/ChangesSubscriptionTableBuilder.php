<?php

namespace Wikibase\Repo\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\LogWarningExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * Implements initial population (priming) for the wb_changes_subscription table,
 * based on the wb_items_per_site. Any wiki linked via the wb_items_per_site table
 * will be considered a subscriber.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangesSubscriptionTableBuilder {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var string
	 */
	private $tableName;

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
	 * @var string 'verbose' or 'standard'
	 */
	private $verbosity;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param EntityIdComposer $entityIdComposer
	 * @param string $tableName
	 * @param int $batchSize
	 * @param string $verbosity Either 'standard' or 'verbose'
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		LoadBalancer $loadBalancer,
		EntityIdComposer $entityIdComposer,
		$tableName,
		$batchSize,
		$verbosity = 'standard'
	) {
		if ( !is_string( $tableName ) ) {
			throw new InvalidArgumentException( '$tableName must be a string' );
		}

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		if ( $verbosity !== 'standard' && $verbosity !== 'verbose' ) {
			throw new InvalidArgumentException( '$verbosity must be either "verbose"'
				. ' or "standard".' );
		}

		$this->loadBalancer = $loadBalancer;
		$this->entityIdComposer = $entityIdComposer;
		$this->tableName = $tableName;
		$this->batchSize = $batchSize;
		$this->verbosity = $verbosity;

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
	 * Fill the subscription table with rows based on entries in wb_items_per_site.
	 *
	 * @param ItemId|null $startItem The item to start with.
	 */
	public function fillSubscriptionTable( ItemId $startItem = null ) {
		$continuation = $startItem === null ? null : [ $startItem->getNumericId(), 0 ];

		while ( true ) {
			$count = $this->processSubscriptionBatch( $continuation );

			if ( $count > 0 ) {
				$this->progressReporter->reportMessage( 'Populating subscription table: '
					. "inserted $count subscriptions, continuing at item #{$continuation[0]}." );
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
	private function processSubscriptionBatch( &$continuation = [] ) {
		$db = $this->loadBalancer->getConnection( DB_MASTER );

		$subscriptionsPerItemBatch = $this->getSubscriptionsPerItemBatch( $db, $continuation );

		if ( empty( $subscriptionsPerItemBatch ) ) {
			return 0;
		}

		$count = $this->insertSubscriptionBatch( $db, $subscriptionsPerItemBatch );

		$this->loadBalancer->reuseConnection( $db );

		return $count;
	}

	/**
	 * @param IDatabase $db
	 * @param array[] $subscriptionsPerItem
	 *
	 * @return int The number of rows inserted.
	 */
	private function insertSubscriptionBatch( IDatabase $db, array $subscriptionsPerItem ) {
		$db->startAtomic( __METHOD__ );

		$c = 0;
		foreach ( $subscriptionsPerItem as $itemId => $subscribers ) {
			$rows = $this->makeSubscriptionRows( $itemId, $subscribers );

			$db->insert(
				$this->tableName,
				$rows,
				__METHOD__,
				[
					'IGNORE'
				]
			);

			if ( $this->verbosity === 'verbose' ) {
				$this->progressReporter->reportMessage( 'Inserted ' . $db->affectedRows()
					. ' into wb_changes_subscription' );
			}

			$c += count( $rows );
		}

		$db->endAtomic( __METHOD__ );
		return $c;
	}

	/**
	 * @param IDatabase $db
	 * @param array &$continuation
	 *
	 * @return array[] An associative array mapping item IDs to lists of site IDs.
	 */
	private function getSubscriptionsPerItemBatch( IDatabase $db, &$continuation = [] ) {
		if ( empty( $continuation ) ) {
			$continuationCondition = '1';
		} else {
			list( $fromItemId, $fromRowId ) = $continuation;
			$continuationCondition = 'ips_item_id > ' . (int)$fromItemId
				. ' OR ( '
					. 'ips_item_id = ' . (int)$fromItemId
					. ' AND '
					. 'ips_row_id > ' . $fromRowId
				. ' )';
		}

		$res = $db->select(
			'wb_items_per_site',
			[ 'ips_row_id', 'ips_item_id', 'ips_site_id' ],
			$continuationCondition,
			__METHOD__,
			[
				'LIMIT' => $this->batchSize,
				'ORDER BY' => 'ips_item_id, ips_row_id'
			]
		);

		if ( $this->verbosity === 'verbose' ) {
			$this->progressReporter->reportMessage( 'Selected ' . $res->numRows() . ' wb_item_per_site records'
				. ' with continuation: ' . $continuationCondition );
		}

		return $this->getSubscriptionsPerItemFromRows( $res, $continuation );
	}

	/**
	 * @param IResultWrapper $res A result set with the ips_item_id and ips_site_id fields
	 *        set for each row.
	 * @param array &$continuation Single item ID => site ID pair or empty.
	 *
	 * @return array[] An associative array mapping item IDs to lists of site IDs.
	 */
	private function getSubscriptionsPerItemFromRows(
		IResultWrapper $res,
		&$continuation = []
	) {
		$subscriptionsPerItem = [];

		$currentItemId = 0;
		$itemId = null;

		foreach ( $res as $row ) {
			if ( $row->ips_item_id != $currentItemId ) {
				$currentItemId = $row->ips_item_id;
				$itemId = $this->entityIdComposer
					->composeEntityId( '', Item::ENTITY_TYPE, $currentItemId )
					->getSerialization();
			}

			$subscriptionsPerItem[$itemId][] = $row->ips_site_id;
			$continuation = [ $currentItemId, $row->ips_row_id ];
		}

		return $subscriptionsPerItem;
	}

	/**
	 * Returns a list of rows for insertion, using Database's multi-row insert mechanism.
	 * Each row is represented as [ $itemId, $subscriber ].
	 *
	 * @param string $itemId
	 * @param string[] $subscribers
	 *
	 * @return array[] rows
	 */
	private function makeSubscriptionRows( $itemId, array $subscribers ) {
		$rows = [];

		foreach ( $subscribers as $subscriber ) {
			$rows[] = [
				'cs_entity_id' => $itemId,
				'cs_subscriber_id' => $subscriber
			];
		}

		return $rows;
	}

}
