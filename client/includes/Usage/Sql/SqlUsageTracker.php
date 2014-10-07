<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use DatabaseBase;
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
	private $batchSize;

	/**
	 * @param EntityIdParser $idParser
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct( EntityIdParser $idParser, LoadBalancer $loadBalancer ) {
		$this->tableName = 'wbc_entity_usage';
		$this->idParser = $idParser;
		$this->loadBalancer = $loadBalancer;
		$this->batchSize = 100;
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
	 * @return EntityUsage[] Usages before the update, in the same form as $usages
	 */
	public function trackUsedEntities( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$db = $this->beginAtomicSection( __METHOD__ );

		try {
			$oldUsage = $this->queryUsageForPage( $db, $pageId );

			$this->modifyUsage( $db, $pageId, $oldUsage, $usages );

			$this->commitAtomicSection( $db, __METHOD__ );
			return $oldUsage;
		} catch ( Exception $ex ) {
			$this->rollbackAtomicSection( $db, __METHOD__ );
			throw $ex;
		}
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param EntityUsage[] $oldUsages
	 * @param EntityUsage[] $newUsages
	 *
	 * @return int The number of usages added or removed
	 */
	private function modifyUsage( DatabaseBase $db, $pageId, array $oldUsages, array $newUsages ) {
		// array_diff calls EntityUsage::__toString and diffs that
		$removed = array_diff( $oldUsages, $newUsages );
		$added = array_diff( $newUsages, $oldUsages );

		$mod = 0;
		$mod += $this->removeUsageForPage( $db, $pageId, $removed );
		$mod += $this->addUsageForPage( $db, $pageId, $added );

		return $mod;
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return int The number of entries removed
	 */
	private function removeUsageForPage( DatabaseBase $db, $pageId, array $usages ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		$bins = $this->binUsages( $usages );
		$c = 0;

		foreach ( $bins as $aspect => $entityIds ) {
			$c += $this->removeAspectForPage( $db, $pageId, $aspect, array_keys( $entityIds ) );
		}

		return $c;
	}

	/**
	 * Collects the EntityIds contained in the given list of EntityUsages into
	 * bins based on the usage's aspect.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[] an associative array mapping aspect ids to lists of EntityIds.
	 */
	private function binUsages( array $usages ) {
		$bins = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$aspect = $usage->getAspect();
			$id = $usage->getEntityId();
			$key = $id->getSerialization();

			$bins[$aspect][$key] = $id;
		}

		return $bins;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[] A list of rows for use with DatabaseBase::insert
	 */
	private function makeUsageRows( $pageId, array $usages ) {
		$rows = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$rows[] = array(
				'eu_page_id' => (int)$pageId,
				'eu_aspect' => (string)$usage->getAspect(),
				'eu_entity_id' => (string)$usage->getEntityId()->getSerialization(),
				'eu_entity_type' => (string)$usage->getEntityId()->getEntityType(),
			);
		}

		return $rows;
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param string $aspect
	 * @param string[] $entityIds
	 *
	 * @return int The number of entries removed
	 */
	private function removeAspectForPage( DatabaseBase $db, $pageId, $aspect, array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return 0;
		}

		$batches = array_chunk( $entityIds, $this->batchSize );
		$c = 0;

		foreach ( $batches as $batch ) {
			$db->delete(
				$this->tableName,
				array(
					'eu_page_id' => $pageId,
					'eu_aspect' => $aspect,
					'eu_entity_id' => $batch,
				),
				__METHOD__
			);

			$c += $db->affectedRows();
		}

		return $c;
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return int The number of entries added
	 */
	private function addUsageForPage( DatabaseBase $db, $pageId, array $usages ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		$batches = array_chunk(
			$this->makeUsageRows( $pageId, $usages ),
			$this->batchSize
		);

		$c = 0;

		foreach ( $batches as $rows ) {
			$db->insert(
				$this->tableName,
				$rows,
				__METHOD__
			);

			$c += $db->affectedRows();
		}

		return $c;
	}

	/**
	 * Removes usage tracking for the given set of entities.
	 * This is used typically when entities were deleted.
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

		$db = $this->beginAtomicSection( __METHOD__ );

		try {
			$batches = array_chunk( $entityIds, $this->batchSize );

			foreach ( $batches as $batch ) {
				$db->delete(
					$this->tableName,
					array(
						'eu_entity_id' => $batch,
					),
					__METHOD__
				);
			}

			$this->commitAtomicSection( $db, __METHOD__ );
		} catch ( Exception $ex ) {
			$this->rollbackAtomicSection( $db, __METHOD__ );
			throw $ex;
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
			$key = $usage->toString();

			$usages[$key] = $usage;
		}

		return $usages;
	}

	/**
	 * @see UsageTracker::getPagesUsing
	 *
	 * @param EntityId[] $entities
	 * @param array $aspects
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

		if ( !empty( $aspects ) ) {
			$where['eu_aspect'] = $aspects;
		}

		$db = $this->getReadConnection();

		$res = $db->select(
			$this->tableName,
			array( 'eu_entity_id' ),
			$where,
			__METHOD__
		);

		$this->releaseConnection( $db );

		return $this->extractProperties( 'eu_entity_id', $res );
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
		$newArr = [];

		foreach( $arr as $item ) {
			$newArr[] = $item->$key;
		}

		return $newArr;
	}

}
