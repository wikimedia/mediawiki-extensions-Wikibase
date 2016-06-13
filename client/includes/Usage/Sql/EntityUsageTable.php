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
 * @author Marius Hoch
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
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return int[] affected row ids
	 * @throws DBUnexpectedError
	 * @throws MWException
	 */
	private function getAffectedRowIds( $pageId, array $usages ) {
		$usageConditions = array();
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
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[]
	 */
	private function makeUsageRows( $pageId, array $usages ) {
		$rows = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$rows[] = array(
				'eu_page_id' => (int)$pageId,
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization()
			);
		}

		return $rows;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return int The number of entries added
	 */
	public function addUsages( $pageId, array $usages ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		$batches = array_chunk(
			$this->makeUsageRows( $pageId, $usages ),
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
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[] EntityUsage identity string => EntityUsage
	 */
	public function queryUsages( $pageId ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$res = $this->connection->select(
			$this->tableName,
			[ 'eu_aspect', 'eu_entity_id' ],
			[ 'eu_page_id' => $pageId ],
			__METHOD__
		);

		return $this->convertRowsToUsages( $res );
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
	 * Removes all usage tracking for a given page.
	 *
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	public function pruneUsages( $pageId ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$old = $this->queryUsages( $pageId );

		$this->removeUsages( $pageId, $old );

		return $old;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 */
	public function removeUsages( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}
		if ( empty( $usages ) ) {
			return;
		}

		$rowIds = $this->getAffectedRowIds( $pageId, $usages );
		$rowIdChunks = array_chunk( $rowIds, $this->batchSize );

		foreach ( $rowIdChunks as $chunk ) {
			$this->connection->begin( __METHOD__ );

			$this->connection->delete(
				$this->tableName,
				[
					'eu_row_id' => $chunk,
				],
				__METHOD__
			);

			$this->connection->commit( __METHOD__ );
		}
	}

	/**
	 * @see UsageLookup::getPagesUsing
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects
	 *
	 * @return Traversable A traversable over PageEntityUsages grouped by page.
	 */
	public function getPagesUsing( array $entityIds, array $aspects = array() ) {
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
		$usagesPerPage = array();

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
			return array();
		}

		$entityIdMap = array();

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
		$subQueries = [];

		foreach ( $idStrings as $idString ) {
			$subQueries[] = $this->connection->selectSQLText(
				$this->tableName,
				'eu_entity_id',
				[ 'eu_entity_id' => $idString ],
				'',
				[ 'LIMIT' => 1 ]
			);
		}

		$values = [];
		foreach ( $subQueries as $sql ) {
			$res = $this->connection->query( $sql, __METHOD__ );
			if ( $res->numRows() ) {
				$values[] = $res->current()->eu_entity_id;
			}
		}

		return $values;
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

		return array_map( 'intval', $rowIds ?: array() );
	}

}
