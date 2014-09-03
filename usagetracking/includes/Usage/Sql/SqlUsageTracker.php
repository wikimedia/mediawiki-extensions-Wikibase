<?php

namespace Wikibase\Usage\Sql;

use DatabaseBase;
use Exception;
use Iterator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Usage\UsageTracker;
use Wikibase\Usage\UsageTrackerException;

/**
 * An SQL based usage tracker implementation.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlUsageTracker implements UsageTracker {

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
	private function reindexEntityIds( $ids ) {
		$reindexed = array();

		foreach ( $ids as $id ) {
			$key = $id->getSerialization();
			$reindexed[$key] = $id;
		}

		return $reindexed;
	}

	/**
	 * @see UsageTracker::updateUsageForPage
	 *
	 * @param int $pageId
	 * @param array $usages
	 *
	 * @throws UsageTrackerException
	 */
	public function updateUsageForPage( $pageId, array $usages ) {
		$oldUsage = $this->getUsageForPage( $pageId );

		$db = $this->beginWriteTransaction();

		try {
			foreach ( $usages as $aspect => $newEntities ) {
				$oldEntities = isset( $oldUsage[$aspect] ) ? $oldUsage[$aspect] : array();

				$this->modifyUsage( $db, $pageId, $aspect, $oldEntities, $newEntities );
			}

			$this->commitWriteTransaction( $db );
		} catch ( Exception $ex ) {
			$this->rollbackWriteTransaction( $db );
			throw $ex;
		}

	}

	public function modifyUsage( DatabaseBase $db, $pageId, $aspect, array $oldEntities, array $newEntities ) {
		$newEntities = $this->reindexEntityIds( $newEntities );
		$oldEntities = $this->reindexEntityIds( $oldEntities );

		$removed = array_diff_key( $oldEntities, $newEntities );
		$added = array_diff_key( $newEntities, $oldEntities );

		$this->removeUsageForPage( $db, $pageId, $aspect, $removed );
		$this->addUsageForPage( $db, $pageId, $aspect, $added );
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param string $aspect
	 * @param EntityId[] $entities Must by keys by string id
	 */
	private function removeUsageForPage( DatabaseBase $db, $pageId, $aspect, array $entities ) {
		if ( empty( $entities ) ) {
			return;
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
	}

	/**
	 * @param DatabaseBase $db
	 * @param int $pageId
	 * @param string $aspect
	 * @param EntityId[] $entities Must by keys by string id
	 */
	private function addUsageForPage( DatabaseBase $db, $pageId, $aspect, array $entities ) {
		if ( empty( $entities ) ) {
			return;
		}

		// assuming we are in a transaction, doing inserts in a loop should be fine.
		foreach ( $entities as $key => $id ) {
			$db->insert(
				$this->table,
				array(
					'eu_page_id' => (int)$pageId,
					'eu_aspect' => (string)$aspect,
					'eu_entity_id' => (string)$key,
					'eu_entity_type' => $id->getEntityType(),
				),
				__METHOD__
			);
		}
	}

	/**
	 * @see UsageTracker::getUsageForPage
	 *
	 * @param int $pageId
	 *
	 * @return array An associative array mapping aspect identifiers to lists of EntityIds.
	 * @throws UsageTrackerException
	 */
	public function getUsageForPage( $pageId ) {
		$db = $this->getReadConnection();

		$res = $db->select(
			$this->table,
			array( 'eu_aspect', 'eu_entity_id' ),
			array( 'eu_page_id' => (int)$pageId ),
			__METHOD__
		);

		$usages = $this->convertRowsToUsageByAspect( $res );

		$this->releaseConnection( $db );
		return $usages;
	}

	/**
	 * @param array|Iterator $rows
	 *
	 * @return array
	 */
	private function convertRowsToUsageByAspect( $rows ) {
		$usages = array();
		foreach ( $rows as $row ) {
			$id = $this->idParser->parse( $row->eu_entity_id );
			$key = $id->getSerialization();
			$aspect = $row->eu_aspect;

			$usages[$aspect][$key] = $id;
		}

		return $usages;
	}

	/**
	 * @see UsageTracker::getPagesUsing
	 *
	 * @param EntityId[] $entities
	 * @param array $aspects
	 *
	 * @return int[] A list of page ids.
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entities, array $aspects = array() ) {
		$entities = $this->reindexEntityIds( $entities );

		$where = array( 'eu_entity_id' => array_keys( $entities ) );

		if ( !empty( $aspects ) ) {
			$where['eu_aspect'] = $aspects;
		}

		$db = $this->getReadConnection();

		$res = $db->select(
			$this->table,
			array( 'eu_aspect', 'eu_entity_id' ),
			$where,
			__METHOD__
		);

		$pages = $this->convertRowsToPageIds( $res );

		$this->releaseConnection( $db );
		return $pages;
	}

	/**
	 * @param array|Iterator $rows
	 *
	 * @return array
	 */
	private function convertRowsToPageIds( $rows ) {
		$pages = array();
		foreach ( $rows as $row ) {
			$pages[] = $row->eu_page_id;
		}

		return $pages;
	}

}
 