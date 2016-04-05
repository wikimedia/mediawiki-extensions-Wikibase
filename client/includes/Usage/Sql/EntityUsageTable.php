<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use DatabaseBase;
use DBUnexpectedError;
use InvalidArgumentException;
use MWException;
use Traversable;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Helper class for updating the wbc_entity_usage table.
 * This is used internally by SqlUsageTracker.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @internal
 */
class EntityUsageTable {

	const DEFAULT_TABLE_NAME = 'wbc_entity_usage';

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var DatabaseBase
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @param EntityIdParser $idParser
	 * @param DatabaseBase $connection
	 * @param int $batchSize defaults to 100
	 * @param string|null $tableName defaults to wbc_entity_usage
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdParser $idParser,
		DatabaseBase $connection,
		$batchSize = 100,
		$tableName = null
	) {
		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		if ( !is_string( $tableName ) && $tableName !== null ) {
			throw new InvalidArgumentException( '$tableName must be a string or null' );
		}

		$this->idParser = $idParser;
		$this->connection = $connection;
		$this->batchSize = $batchSize;
		$this->tableName = $tableName ?: self::DEFAULT_TABLE_NAME;
	}

	/**
	 * Sets the "touched" timestamp for the given usages.
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string $touched timestamp in any format MWTimestamp accepts
	 */
	public function touchUsages( $pageId, array $usages, $touched ) {
		if ( empty( $usages ) ) {
			return;
		}

		$rowIds = $this->getAffectedRowIds( $pageId, $usages );
		$batches = array_chunk( $rowIds, $this->batchSize );

		foreach ( $batches as $batch ) {
			$this->touchUsageBatch( $batch, $touched );
		}
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return int[] affected row ids
	 * @throws DBUnexpectedError
	 * @throws MWException
	 */
	private function getAffectedRowIds( $pageId, array $usages ) {
		$usageConditions = [];
		$db = $this->connection;

		foreach ( $usages as $usage ) {
			$usageConditions[] = $db->makeList( array(
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
			), LIST_AND );
		}

		// Collect affected row IDs, so we can use them for an
		// efficient update query on the master db.
		$where = array(
			'eu_page_id' => (int)$pageId,
			$db->makeList( $usageConditions, LIST_OR )
		);
		return $this->getPrimaryKeys( $where, __METHOD__ );
	}

	/**
	 * @param int[] $rowIds the ids of the rows to touch
	 * @param string $touched timestamp
	 */
	private function touchUsageBatch( array $rowIds, $touched ) {
		$this->connection->begin( __METHOD__ );
		$this->connection->update(
			$this->tableName,
			array(
				'eu_touched' => wfTimestamp( TS_MW, $touched ),
			),
			array(
				'eu_row_id' => $rowIds
			),
			__METHOD__
		);
		$this->connection->commit( __METHOD__ );
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string $touched timestamp
	 *
	 * @throws InvalidArgumentException
	 * @return array[]
	 */
	private function makeUsageRows( $pageId, array $usages, $touched ) {
		$rows = [];

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$rows[] = array(
				'eu_page_id' => (int)$pageId,
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
				'eu_touched' => wfTimestamp( TS_MW, $touched ),
			);
		}

		return $rows;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string|false $touched timestamp, may be false only if $usages is empty.
	 *
	 * @throws InvalidArgumentException
	 * @return int The number of entries added
	 */
	public function addUsages( $pageId, array $usages, $touched ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		if ( !is_string( $touched ) || $touched === '' ) {
			throw new InvalidArgumentException( '$touched must be a timestamp string.' );
		}

		$batches = array_chunk(
			$this->makeUsageRows( $pageId, $usages, $touched ),
			$this->batchSize
		);

		$c = 0;

		foreach ( $batches as $rows ) {
			$this->connection->begin( __METHOD__ );

			$this->connection->insert( $this->tableName, $rows, __METHOD__, array( 'IGNORE' ) );
			$c += $this->connection->affectedRows();

			$this->connection->commit( __METHOD__ );
		}

		return $c;
	}

	/**
	 * @param int $pageId
	 * @param string|null $timeOp Operator to use with $timestamp, e.g. "<" or ">=".
	 * @param string|null $timestamp
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	public function queryUsages( $pageId, $timeOp = null, $timestamp = null ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		if ( $timeOp !== null && ( !is_string( $timeOp ) || $timeOp === '' ) ) {
			throw new InvalidArgumentException( '$timeOp must be an operator.' );
		}

		if ( $timestamp !== null && ( !is_string( $timestamp ) || $timestamp === '' ) ) {
			throw new InvalidArgumentException( '$timestamp must be a timestamp string.' );
		}

		if ( is_string( $timeOp ) !== is_string( $timestamp ) ) {
			throw new InvalidArgumentException( '$timeOp and $timestamp must either both be null, or both be strings.' );
		}

		$where = array(
			'eu_page_id' => $pageId,
		);

		switch ( $timeOp ) {
			case null:
				break;

			case '<':
				$where[] = 'eu_touched < ' . $this->connection->addQuotes( $timestamp );
				break;

			case '>=':
				$where[] = 'eu_touched >= ' . $this->connection->addQuotes( $timestamp );
				break;

			default:
				throw new InvalidArgumentException( '$timeOp must be one of the allowed comparison operators.' );
		}

		$res = $this->connection->select(
			$this->tableName,
			array( 'eu_aspect', 'eu_entity_id' ),
			$where,
			__METHOD__
		);

		$usages = $this->convertRowsToUsages( $res );
		return $usages;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return string[]
	 */
	private function getEntityIdStrings( array $entityIds ) {
		return array_map( function( EntityId $id ) {
			return $id->getSerialization();
		}, $entityIds );
	}

	/**
	 * @param Traversable $rows
	 *
	 * @return EntityUsage[]
	 */
	private function convertRowsToUsages( Traversable $rows ) {
		$usages = [];

		foreach ( $rows as $object ) {
			$entityId = $this->idParser->parse( $object->eu_entity_id );
			list( $aspect, $modifier ) = EntityUsage::splitAspectKey( $object->eu_aspect );

			$usage = new EntityUsage( $entityId, $aspect, $modifier );
			$key = $usage->getIdentityString();
			$usages[$key] = $usage;
		}

		return $usages;
	}

	/**
	 * Removes usage tracking entries that were last updated before the given
	 * timestamp.
	 *
	 * @param int $pageId
	 * @param string $lastUpdatedBefore timestamp; if empty, '00000000000000' is assumed
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	public function pruneStaleUsages( $pageId, $lastUpdatedBefore ) {
		if ( !is_string( $lastUpdatedBefore ) ) {
			throw new InvalidArgumentException( '$lastUpdatedBefore must be a string' );
		}

		if ( $lastUpdatedBefore === '' ) {
			// treat '' as '00000000000000' instead of `now`.
			return [];
		}

		$lastUpdatedBefore = wfTimestamp( TS_MW, $lastUpdatedBefore );

		$old = $this->queryUsages( $pageId, '<', $lastUpdatedBefore );

		do {
			$where = array(
				'eu_page_id' => (int)$pageId,
				'eu_touched < ' . $this->connection->addQuotes( $lastUpdatedBefore ),
			);
			$res = $this->getPrimaryKeys( $where, __METHOD__ );

			if ( !$res ) {
				break;
			}

			$this->connection->begin( __METHOD__ );

			$this->connection->delete(
				$this->tableName,
				array(
					'eu_row_id' => $res,
				),
				__METHOD__
			);

			$this->connection->commit( __METHOD__ );
		} while ( count( $res ) === $this->batchSize );

		return $old;
	}

	/**
	 * @see UsageLookup::getPagesUsing
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects
	 *
	 * @return Traversable A traversable over PageEntityUsages grouped by page.
	 */
	public function getPagesUsing( array $entityIds, array $aspects = [] ) {
		if ( empty( $entityIds ) ) {
			return new ArrayIterator();
		}

		$idStrings = $this->getEntityIdStrings( $entityIds );
		$where = array( 'eu_entity_id' => $idStrings );

		if ( !empty( $aspects ) ) {
			$where['eu_aspect'] = $aspects;
		}

		$res = $this->connection->select(
			$this->tableName,
			array( 'eu_page_id', 'eu_entity_id', 'eu_aspect' ),
			$where,
			__METHOD__
		);

		$pages = $this->foldRowsIntoPageEntityUsages( $res );

		//TODO: use paging for large page sets!
		return new ArrayIterator( $pages );
	}

	/**
	 * @param Traversable $rows
	 *
	 * @return PageEntityUsages[]
	 */
	private function foldRowsIntoPageEntityUsages( Traversable $rows ) {
		$usagesPerPage = [];

		foreach ( $rows as $row ) {
			$pageId = (int)$row->eu_page_id;

			if ( isset( $usagesPerPage[$pageId] ) ) {
				$pageEntityUsages = $usagesPerPage[$pageId];
			} else {
				$pageEntityUsages = new PageEntityUsages( $pageId );
			}

			$entityId = $this->idParser->parse( $row->eu_entity_id );
			list( $aspect, $modifier ) = EntityUsage::splitAspectKey( $row->eu_aspect );

			$usage = new EntityUsage( $entityId, $aspect, $modifier );
			$pageEntityUsages->addUsages( array( $usage ) );

			$usagesPerPage[$pageId] = $pageEntityUsages;
		}

		return $usagesPerPage;
	}

	/**
	 * @see UsageLookup::getUnusedEntities
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[]
	 */
	public function getUnusedEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return [];
		}

		$entityIdMap = [];

		foreach ( $entityIds as $entityId ) {
			$idString = $entityId->getSerialization();
			$entityIdMap[$idString] = $entityId;
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

		return $this->connection->selectFieldValues(
			$this->tableName,
			'eu_entity_id',
			$where,
			__METHOD__,
			array( 'DISTINCT' )
		);
	}

	/**
	 * Returns the primary keys for the given where clause.
	 *
	 * @param array $where
	 * @param string $method Calling method
	 *
	 * @return int[]
	 */
	private function getPrimaryKeys( array $where, $method ) {
		$rowIds = $this->connection->selectFieldValues(
			$this->tableName,
			'eu_row_id',
			$where,
			$method
		);

		return array_map( 'intval', $rowIds ?: [] );
	}

}
