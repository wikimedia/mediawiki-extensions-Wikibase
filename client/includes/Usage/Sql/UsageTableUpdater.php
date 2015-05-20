<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseBase;
use InvalidArgumentException;
use Iterator;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Helper class for updating the wb_entity_usage table.
 * This is used internally by SqlUsageTracker.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageTableUpdater {

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
	 * @param string $tableName
	 * @param int $batchSize
	 */
	public function __construct( EntityIdParser $idParser, DatabaseBase $connection, $tableName, $batchSize ) {
		if ( !is_string( $tableName ) ) {
			throw new InvalidArgumentException( '$tableName must be a string' );
		}

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		$this->idParser = $idParser;
		$this->connection = $connection;
		$this->tableName = $tableName;
		$this->batchSize = $batchSize;
	}

	/**
	 * Sets the "touched" timestamp for the given usages.
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string $touched timestamp
	 */
	public function touchUsages( $pageId, array $usages, $touched ) {
		if ( empty( $usages ) ) {
			return;
		}

		$db = $this->connection;

		$usageConditions = array();

		foreach ( $usages as $usage ) {
			$usageConditions[] = $db->makeList( array(
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
			), LIST_AND );
		}

		// XXX: Do we need batching here? List pages may be using hundreds of entities...
		$this->connection->update(
			$this->tableName,
			array(
				'eu_touched' => wfTimestamp( TS_MW, $touched ),
			),
			array(
				'eu_page_id' => (int)$pageId,
				$this->connection->makeList( $usageConditions, LIST_OR )
			),
			__METHOD__
		);
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
		$rows = array();

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
			$this->connection->insert( $this->tableName, $rows, __METHOD__, array( 'IGNORE' ) );
			$c += $this->connection->affectedRows();
		}

		return $c;
	}

	/**
	 * @param int $pageId
	 * @param string|null $timeOp Operator to use with $timestamp, e.g. "<" or ">=".
	 * @param string|null $timestamp
	 *
	 * @return EntityUsage[]
	 *
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
			'wbc_entity_usage',
			array( 'eu_aspect', 'eu_entity_id' ),
			$where,
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
	 * @see UsageTracker::pruneStaleUsages
	 *
	 * @param int $pageId
	 * @param string $lastUpdatedBefore timestamp; if empty, '00000000000000' is assumed
	 *
	 * @return EntityUsage[]
	 */
	public function pruneStaleUsages( $pageId, $lastUpdatedBefore ) {
		if ( !is_string( $lastUpdatedBefore ) ) {
			throw new InvalidArgumentException( '$lastUpdatedBefore must be a string' );
		}

		if ( $lastUpdatedBefore === '' ) {
			// treat '' as '00000000000000' instead of `now`.
			return array();
		}

		$lastUpdatedBefore = wfTimestamp( TS_MW, $lastUpdatedBefore );

		$old = $this->queryUsages( $pageId, '<', $lastUpdatedBefore );

		// XXX: we may want to batch this, based on the data in $old
		$this->connection->delete(
			$this->tableName,
			array(
				'eu_page_id' => (int)$pageId,
				'eu_touched < ' . $this->connection->addQuotes( $lastUpdatedBefore ),
			),
			__METHOD__
		);

		return $old;
	}

	/**
	 * Removes usage tracking for the given set of entities.
	 * This is used typically when entities were deleted.
	 *
	 * @see UsageTracker::removeEntities
	 *
	 * @param string[] $idStrings
	 */
	public function removeEntities( array $idStrings ) {
		if ( empty( $idStrings ) ) {
			return;
		}

		$batches = array_chunk( $idStrings, $this->batchSize );

		foreach ( $batches as $batch ) {
			$this->connection->delete(
				$this->tableName,
				array(
					'eu_entity_id' => $batch,
				),
				__METHOD__
			);
		}
	}

}
