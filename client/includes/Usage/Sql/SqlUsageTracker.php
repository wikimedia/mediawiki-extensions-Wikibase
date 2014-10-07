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
	 * @param EntityId[] $entityIds
	 *
	 * @return string[]
	 */
	private function getEntityIdStrings( array $entityIds ) {
		return array_map( function( EntityId $entityId ) {
			return $entityId->getSerialization();
		}, $entityIds );
	}

	/**
	 * @see UsageTracker::trackUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @throws UsageTrackerException
	 * @throws Exception
	 * @return EntityUsage[] Usages before the update, in the same form as $usages
	 */
	public function trackUsedEntities( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$db = $this->beginAtomicSection( __METHOD__ );

		try {
			$oldUsages = $this->queryUsagesForPage( $db, $pageId );

			$tableUpdater = $this->newTableUpdater( $db );
			$tableUpdater->updateUsage( $pageId, $oldUsages, $usages );

			$this->commitAtomicSection( $db, __METHOD__ );
			return $oldUsages;
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
	 * @param EntityId[] $entityIds
	 *
	 * @throws UsageTrackerException
	 * @throws Exception
	 */
	public function removeEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return;
		}

		$idStrings = $this->getEntityIdStrings( $entityIds );

		$db = $this->beginAtomicSection( __METHOD__ );

		try {
			$tableUpdater = $this->newTableUpdater( $db );
			$tableUpdater->removeEntities( $idStrings );

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
	public function getUsagesForPage( $pageId ) {
		$db = $this->getReadConnection();

		$usages = $this->queryUsagesForPage( $db, $pageId );

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
	private function queryUsagesForPage( DatabaseBase $db, $pageId ) {
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

		foreach ( $rows as $object ) {
			$entityId = $this->idParser->parse( $object->eu_entity_id );
			$usage = new EntityUsage( $entityId, $object->eu_aspect );
			$usages[$usage->getIdentityString()] = $usage;
		}

		return $usages;
	}

	/**
	 * @see UsageTracker::getPagesUsing
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects
	 *
	 * @return Iterator An iterator over page IDs.
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entityIds, array $aspects = array() ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		$idStrings = $this->getEntityIdStrings( $entityIds );
		$where = array( 'eu_entity_id' => $idStrings );

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

		$pages = $this->extractProperty( $res, 'eu_page_id' );

		$this->releaseConnection( $db );

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

		$entityIdMap = array();

		foreach ( $entityIds as $entityId ) {
			$entityIdMap[$entityId->getSerialization()] = $entityId;
		}

		$usedIdStrings = $this->getUsedEntityIdStrings( array_keys( $entityIdMap ) );

		return array_diff_key( $entityIdMap, array_flip( $usedIdStrings ) );
	}

	/**
	 * Returns those entity ids which are used from a given set of entity ids.
	 *
	 * @param string[] $idStrings
	 *
	 * @return string[]
	 */
	private function getUsedEntityIdStrings( array $idStrings ) {
		$where = array( 'eu_entity_id' => $idStrings );

		$db = $this->getReadConnection();

		$res = $db->select(
			$this->tableName,
			array( 'eu_entity_id' ),
			$where,
			__METHOD__
		);

		$this->releaseConnection( $db );

		return $this->extractProperty( $res, 'eu_entity_id' );
	}

	/**
	 * Returns an array of values extracted from the $key property from each object.
	 *
	 * @param array|Iterator $objects
	 * @param string $key
	 *
	 * @return array
	 */
	private function extractProperty( $objects, $key ) {
		$array = array();

		foreach ( $objects as $object ) {
			$array[] = $object->$key;
		}

		return $array;
	}

}
