<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use DatabaseBase;
use DBError;
use Exception;
use InvalidArgumentException;
use Iterator;
use LoadBalancer;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\Usage\UsageTrackerException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * An SQL based usage tracker implementation.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlUsageTracker implements UsageTracker, UsageLookup {

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var int
	 */
	private $batchSize = 1000;

	/**
	 * @param EntityIdParser $idParser
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct( EntityIdParser $idParser, LoadBalancer $loadBalancer ) {
		$this->tableName = 'wbc_entity_usage';
		$this->idParser = $idParser;
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param DatabaseBase $db
	 *
	 * @return UsageTableUpdater
	 */
	private function newTableUpdater( DatabaseBase $db ) {
		return new UsageTableUpdater( $db, $this->tableName, $this->batchSize );
	}

	/**
	 * Sets the query batch size.
	 *
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * Returns the current query batch size.
	 *
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @return DatabaseBase
	 */
	private function getReadConnection() {
		return $this->loadBalancer->getConnection( DB_READ );
	}

	/**
	 * @return DatabaseBase
	 */
	private function getWriteConnection() {
		return $this->loadBalancer->getConnection( DB_WRITE );
	}

	/**
	 * @param DatabaseBase $db
	 */
	private function releaseConnection( DatabaseBase $db ) {
		$this->loadBalancer->reuseConnection( $db );
	}

	/**
	 * @param string $fname
	 *
	 * @return DatabaseBase
	 */
	private function beginAtomicSection( $fname = __METHOD__ ) {
		$db = $this->getWriteConnection();
		$db->startAtomic( $fname );
		return $db;
	}

	/**
	 * @param DatabaseBase $db
	 * @param string $fname
	 *
	 * @return DatabaseBase
	 */
	private function commitAtomicSection( DatabaseBase $db, $fname = __METHOD__ ) {
		$db->endAtomic( $fname );
		$this->releaseConnection( $db );
	}

	/**
	 * @param DatabaseBase $db
	 * @param string $fname
	 *
	 * @return DatabaseBase
	 */
	private function rollbackAtomicSection( DatabaseBase $db, $fname = __METHOD__ ) {
		//FIXME: there does not seem to be a clean way to roll back an atomic section?!
		$db->rollback( $fname, 'flush' );
		$this->releaseConnection( $db );
	}

	/**
	 * Re-indexes the given list of EntityIds so that each EntityId can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @throws InvalidArgumentException
	 * @return EntityId[]
	 */
	private function reindexEntityIds( array $entityIds ) {
		$reindexed = array();

		foreach ( $entityIds as $id ) {
			if ( !( $id instanceof EntityId ) ) {
				throw new InvalidArgumentException( '$entityIds must contain EntityId objects.' );
			}

			$key = $id->getSerialization();
			$reindexed[$key] = $id;
		}

		return $reindexed;
	}

	/**
	 * @see UsageTracker::trackUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @throws UsageTrackerException
	 * @return EntityUsage[] Usages before the update, in the same form as $usages
	 */
	public function trackUsedEntities( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$db = $this->beginAtomicSection( __METHOD__ );

		try {
			$oldUsage = $this->queryUsageForPage( $db, $pageId );

			$tableUpdater = $this->newTableUpdater( $db );
			$tableUpdater->updateUsage( $pageId, $oldUsage, $usages );

			$this->commitAtomicSection( $db, __METHOD__ );
			return $oldUsage;
		} catch ( Exception $ex ) {
			$this->rollbackAtomicSection( $db, __METHOD__ );

			if ( $ex instanceof DBError ) {
				throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
			} else {
				throw $ex;
			}
		}
	}

	/**
	 * @see UsageTracker::removeEntities
	 *
	 * @param EntityId[] $entities
	 *
	 * @throws UsageTrackerException
	 */
	public function removeEntities( array $entities ) {
		if ( empty( $entities ) ) {
			return;
		}

		$db = $this->beginAtomicSection( __METHOD__ );

		try {
			$tableUpdater = $this->newTableUpdater( $db );
			$tableUpdater->removeEntities( $entities );

			$this->commitAtomicSection( $db, __METHOD__ );
		} catch ( Exception $ex ) {
			$this->rollbackAtomicSection( $db, __METHOD__ );

			if ( $ex instanceof DBError ) {
				throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
			} else {
				throw $ex;
			}
		}
	}

	/**
	 * @see UsageTracker::getUsageForPage
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 * @throws UsageTrackerException
	 */
	public function getUsageForPage( $pageId ) {
		$db = $this->getReadConnection();

		$usages = $this->queryUsageForPage( $db, $pageId );

		$this->releaseConnection( $db );
		return $usages;
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	private function queryUsageForPage( DatabaseBase $db, $pageId ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$res = $db->select(
			$this->tableName,
			array( 'eu_aspect', 'eu_entity_id' ),
			array( 'eu_page_id' => (int)$pageId ),
			__METHOD__
		);

		$usages = $this->convertRowsToUsages( $res );
		return $usages;
	}

	/**
	 * @param array|Iterator $rows
	 *
	 * @return EntityUsage[]
	 */
	private function convertRowsToUsages( $rows ) {
		$usages = array();
		foreach ( $rows as $row ) {
			$id = $this->idParser->parse( $row->eu_entity_id );

			$usage = new EntityUsage( $id, $row->eu_aspect );
			$key = $usage->getIdentityString();

			$usages[$key] = $usage;
		}

		return $usages;
	}

	/**
	 * @see UsageTracker::getPagesUsing
	 *
	 * @param EntityId[] $entities
	 * @param string[] $aspects
	 *
	 * @return Iterator An iterator over page IDs.
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entities, array $aspects = array() ) {
		if ( empty( $entities ) ) {
			return array();
		}

		$entities = $this->reindexEntityIds( $entities );

		$where = array( 'eu_entity_id' => array_keys( $entities ) );

		if ( !empty( $aspects ) ) {
			$where['eu_aspect'] = $aspects;
		}

		$db = $this->getReadConnection();

		$res = $db->select(
			$this->tableName,
			array( 'DISTINCT eu_page_id' ),
			$where,
			__METHOD__
		);

		$pages = $this->extractProperties( 'eu_page_id', $res );

		$this->releaseConnection( $db );

		//TODO: use paging for large page sets!
		return new ArrayIterator( $pages );
	}

	/**
	 * @see UsageTracker::getUnusedEntities
	 *
	 * @param EntityId[] $entities
	 *
	 * @return EntityId[]
	 * @throws UsageTrackerException
	 */
	public function getUnusedEntities( array $entities ) {
		if ( empty( $entities ) ) {
			return array();
		}

		$entities = $this->reindexEntityIds( $entities );

		$where = array( 'eu_entity_id' => array_keys( $entities ) );

		$db = $this->getReadConnection();

		$res = $db->select(
			$this->tableName,
			array( 'eu_entity_id' ),
			$where,
			__METHOD__
		);

		$this->releaseConnection( $db );

		$entityIds = $this->extractProperties( 'eu_entity_id', $res );
		$unused = $this->stripKeysFromList( $entityIds, $entities );

		return $unused;
	}

	/**
	 * Returns a copy of $arr without all keys that are in $keys.
	 *
	 * @param string[] $keys
	 * @param array $arr
	 *
	 * @return array
	 */
	private function stripKeysFromList( array $keys, array $arr ) {
		return array_diff_key( $arr, array_flip( $keys ) );
	}

	/**
	 * Returns an array of values for $key property from the array $arr.
	 *
	 * @param string $key
	 * @param array|Iterator $arr
	 *
	 * @return array
	 */
	private function extractProperties( $key, $arr ) {
		$newArr = array();

		foreach( $arr as $item ) {
			$newArr[] = $item->$key;
		}

		return $newArr;
	}

}
