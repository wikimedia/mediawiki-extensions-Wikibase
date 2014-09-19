<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use DatabaseBase;
use Exception;
use Iterator;
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
	private $table;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * The DB connection we currently own
	 *
	 * @var DatabaseBase
	 */
	private $currentDb = null;

	/**
	 * Whether we currently own a transaction
	 *
	 * @var bool
	 */
	private $currentTrx = false;

	/**
	 * @param EntityIdParser $idParser
	 */
	function __construct( EntityIdParser $idParser ) {
		$this->table = 'wbc_entity_usage';
		$this->idParser = $idParser;
	}

	/**
	 * @return DatabaseBase
	 */
	private function getReadConnection() {
		return wfGetDB( DB_SLAVE );
	}

	/**
	 * @return DatabaseBase
	 */
	private function getWriteConnection() {
		return wfGetDB( DB_MASTER );
	}

	/**
	 * @param DatabaseBase $db
	 */
	private function releaseConnection( DatabaseBase $db ) {
		$this->currentDb = null;
	}

	private function beginWriteTransaction() {
		$db = $this->getWriteConnection();

		if ( $db->trxLevel() === 0 ) {
			$this->currentTrx = true;
			$db->begin( __METHOD__ );
		} else {
			// there already is a transaction, but we don't own it, so we shouldn't commit it
			$this->currentTrx = false;
		}

		return $db;
	}

	private function commitWriteTransaction( DatabaseBase $db ) {
		if ( $this->currentTrx ) {
			// commit only if we actually own the current transaction
			$db->commit( __METHOD__ );
			$this->currentTrx = false;
		}

		$this->releaseConnection( $db );
	}

	private function rollbackWriteTransaction( DatabaseBase $db ) {
		//NOTE: perform the rollback if there is a transaction, even if we don't own it!
		if ( $db->trxLevel() > 0 ) {
			$db->rollback( __METHOD__ );
			$this->currentTrx = false;
		}

		$this->releaseConnection( $db );
	}

	/**
	 * Re-indexes the given list of EntityIds so that each EntityId can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityId[] $ids
	 * @return EntityId[]
	 */
	private function reindexEntityIds( array $ids ) {
		$reindexed = array();

		foreach ( $ids as $id ) {
			$key = $id->getSerialization();
			$reindexed[$key] = $id;
		}

		return $reindexed;
	}

	/**
	 * Re-indexes the given list of EntityUsagess so that each EntityUsage can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityUsage[] $usages
	 * @return EntityUsage[]
	 */
	private function reindexEntityUsages( array $usages ) {
		$reindexed = array();

		foreach ( $usages as $usage ) {
			$key = $usage->toString();
			$reindexed[$key] = $usage;
		}

		return $reindexed;
	}

	/**
	 * @see UsageTracker::trackUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return EntityUsage[] Usages before the update, in the same form as $usages
	 * @throws UsageTrackerException
	 */
	public function trackUsedEntities( $pageId, array $usages ) {
		$db = $this->beginWriteTransaction();

		try {
			$oldUsage = $this->queryUsageForPage( $db, $pageId );

			$this->modifyUsage( $db, $pageId, $oldUsage, $usages );

			$this->commitWriteTransaction( $db );
			return $oldUsage;
		} catch ( Exception $ex ) {
			$this->rollbackWriteTransaction( $db );
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
		$newUsages = $this->reindexEntityUsages( $newUsages );
		$oldUsages = $this->reindexEntityUsages( $oldUsages );

		$removed = array_diff_key( $oldUsages, $newUsages );
		$added = array_diff_key( $newUsages, $oldUsages );

		$mod = 0;
		$mod += $this->removeUsageForPage( $db, $pageId, $removed );
		$mod += $this->addUsageForPage( $db, $pageId, $added );

		return $mod;
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param EntityUsage[] $usages Must by keys by string id
	 *
	 * @return int The number of entries removed
	 */
	private function removeUsageForPage( DatabaseBase $db, $pageId, array $usages ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		$bins = $this->binUsages( $usages );

		foreach ( $bins as $aspect => $entities ) {
			$this->removeAspectForPage( $db, $pageId, $aspect, $entities );
		}

		return $db->affectedRows();
	}

	/**
	 * Collects the EntityIds contained in the given list of EntityUsages into
	 * bins based on the usage's aspect.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @return array[] an associative array mapping aspect ids to lists of EntityIds.
	 */
	private function binUsages( array $usages ) {
		$bins = array();

		foreach ( $usages as $usage ) {
			$aspect = $usage->getAspect();
			$bins[$aspect][] = $usage->getEntityId();
		}

		return $bins;
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param string $aspect
	 * @param EntityId[] $entities Must by keys by string id
	 *
	 * @return int The number of entries removed
	 */
	private function removeAspectForPage( DatabaseBase $db, $pageId, $aspect, array $entities ) {
		if ( empty( $entities ) ) {
			return 0;
		}

		$db->delete(
			$this->table,
			array(
				'eu_page_id' => (int)$pageId,
				'eu_aspect' => (string)$aspect,
				'eu_entity_id' => array_keys( $entities ),
			),
			__METHOD__
		);

		return $db->affectedRows();
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param EntityUsage[] $usages Must by keys by string id
	 *
	 * @return int The number of entries added
	 */
	private function addUsageForPage( DatabaseBase $db, $pageId, array $usages ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		// Assuming we are in a transaction, doing inserts in a loop should be fine.
		// This loop will be executed once for every entity to which a reference was added
		// by the current edit. Typically, this will be no more than a handful, but when
		// e.g. restoring a large list page after blanking, it may be a thousand entities
		// or more.
		foreach ( $usages as $usage ) {
			$id = $usage->getEntityId();

			$db->insert(
				$this->table,
				array(
					'eu_page_id' => (int)$pageId,
					'eu_aspect' => (string)$usage->getAspect(),
					'eu_entity_id' => (string)$id->getSerialization(),
					'eu_entity_type' => $id->getEntityType(),
				),
				__METHOD__
			);
		}

		return count( $usages );
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

		$entities = $this->reindexEntityIds( $entities );

		$db = $this->beginWriteTransaction();

		try {
			$db->delete(
				$this->table,
				array(
					'eu_entity_id' => array_keys( $entities ),
				),
				__METHOD__
			);

			$this->commitWriteTransaction( $db );
		} catch ( Exception $ex ) {
			$this->rollbackWriteTransaction( $db );
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
	 * @param int $pageId
	 * @param DatabaseBase $db
	 *
	 * @return EntityUsage[]
	 */
	private function queryUsageForPage( DatabaseBase $db, $pageId ) {
		$res = $db->select(
			$this->table,
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

		$entities = $this->reindexEntityIds( $entities );

		$where = array( 'eu_entity_id' => array_keys( $entities ) );

		if ( !empty( $aspects ) ) {
			$where['eu_aspect'] = $aspects;
		}

		$db = $this->getReadConnection();

		$res = $db->select(
			$this->table,
			array( 'DISTINCT eu_page_id' ),
			$where,
			__METHOD__
		);

		$pages = $this->convertRowsToPageIds( $res );

		$this->releaseConnection( $db );

		//TODO: use paging for large page sets!
		return new ArrayIterator( $pages );
	}

	/**
	 * @param array|Iterator $rows
	 *
	 * @return array
	 */
	private function convertRowsToPageIds( $rows ) {
		$pages = array();
		foreach ( $rows as $row ) {
			$pages[] = (int)$row->eu_page_id;
		}

		return $pages;
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

		if ( !empty( $aspects ) ) {
			$where['eu_aspect'] = $aspects;
		}

		$db = $this->getReadConnection();

		$res = $db->select(
			$this->table,
			array( 'eu_entity_id' ),
			$where,
			__METHOD__
		);

		$unused = $this->stripEntitiesFromList( $res, $entities );

		$this->releaseConnection( $db );
		return $unused;
	}

	/**
	 * Unsets all keys in $entities that where found as values of eu_entity_id
	 * in $rows.
	 *
	 * @param array|Iterator $rows
	 * @param EntityId[] $entities
	 *
	 * @return array
	 */
	private function stripEntitiesFromList( $rows, array $entities ) {
		foreach ( $rows as $row ) {
			$key = $row->eu_entity_id;
			unset( $entities[$key] );
		}

		return $entities;
	}

}
 