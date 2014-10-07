<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use DatabaseBase;
use DBError;
use Exception;
use InvalidArgumentException;
use Iterator;
use LoadBalancer;
use Wikibase\Client\Store\Sql\ConnectionManager;
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
	 * @var ConnectionManager
	 */
	private $connectionManager;

	/**
	 * @var int
	 */
	private $batchSize = 1000;

	/**
	 * @param EntityIdParser $idParser
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct( EntityIdParser $idParser, ConnectionManager $connectionManager ) {
		$this->tableName = 'wbc_entity_usage';
		$this->idParser = $idParser;
		$this->connectionManager = $connectionManager;
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
	 * Returns the string serialization of an EntityId.
	 *
	 * @param EntityId $entityId
	 * @return string
	 */
	private function getEntityIdSerialization( EntityId $entityId ) {
		return $entityId->getSerialization();
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

		$db = $this->connectionManager->beginAtomicSection( __METHOD__ );

		try {
			$oldUsage = $this->queryUsageForPage( $db, $pageId );

			$tableUpdater = $this->newTableUpdater( $db );
			$tableUpdater->updateUsage( $pageId, $oldUsage, $usages );

			$this->connectionManager->commitAtomicSection( $db, __METHOD__ );
			return $oldUsage;
		} catch ( Exception $ex ) {
			$this->connectionManager->rollbackAtomicSection( $db, __METHOD__ );

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

		$entityIds = array_map( array( $this, 'getEntityIdSerialization' ), $entities );

		$db = $this->connectionManager->beginAtomicSection( __METHOD__ );

		try {
			$tableUpdater = $this->newTableUpdater( $db );
			$tableUpdater->removeEntities( $entityIds );

			$this->connectionManager->commitAtomicSection( $db, __METHOD__ );
		} catch ( Exception $ex ) {
			$this->connectionManager->rollbackAtomicSection( $db, __METHOD__ );

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
		$db = $this->connectionManager->getReadConnection();

		$usages = $this->queryUsageForPage( $db, $pageId );

		$this->connectionManager->releaseConnection( $db );
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

		$entityIds = array_map( array( $this, 'getEntityIdSerialization' ), $entities );

		$where = array( 'eu_entity_id' => $entityIds );

		if ( !empty( $aspects ) ) {
			$where['eu_aspect'] = $aspects;
		}

		$db = $this->connectionManager->getReadConnection();

		$res = $db->select(
			$this->tableName,
			array( 'DISTINCT eu_page_id' ),
			$where,
			__METHOD__
		);

		$pages = $this->extractProperty( 'eu_page_id', $res );

		$this->connectionManager->releaseConnection( $db );

		//TODO: use paging for large page sets!
		return new ArrayIterator( $pages );
	}

	/**
	 * @see UsageTracker::getUnusedEntities
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[]
	 * @throws UsageTrackerException
	 */
	public function getUnusedEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		$entityIdsBySerialization = array();
		$entityIdStrings = array();

		foreach ( $entityIds as $id ) {
			$serialization = $this->getEntityIdSerialization( $id );
			$entityIdStrings[] = $serialization;
			$entityIdsBySerialization[ $serialization ] = $id;
		}

		$usedEntityIdStrings = $this->getUsedEntities( $entityIdStrings );

		$unusedEntityIdStrings = array_diff( $entityIdStrings, $usedEntityIdStrings );

		$unusedEntityIds = array_intersect_key(
			$entityIdsBySerialization,
			array_flip( $unusedEntityIdStrings )
		);

		return $unusedEntityIds;
	}

	/**
	 * Returns those entity ids which are used from a given set of entity ids.
	 *
	 * @param string[] $entityIds
	 * @return string[]
	 */
	private function getUsedEntities( array $entityIds ) {
		$where = array( 'eu_entity_id' => $entityIds );

		$db = $this->connectionManager->getReadConnection();

		$res = $db->select(
			$this->tableName,
			array( 'eu_entity_id' ),
			$where,
			__METHOD__
		);

		$this->connectionManager->releaseConnection( $db );

		return $this->extractProperty( 'eu_entity_id', $res );
	}

	/**
	 * Returns an array of values for $key property from the array $arr.
	 *
	 * @param string $key
	 * @param array|Iterator $arr
	 *
	 * @return array
	 */
	private function extractProperty( $key, $arr ) {
		$newArr = array();

		foreach( $arr as $arrItem ) {
			$newArr[] = $arrItem->$key;
		}

		return $newArr;
	}

}
