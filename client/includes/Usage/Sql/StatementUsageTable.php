<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use Database;
use DBUnexpectedError;
use InvalidArgumentException;
use MWException;
use Traversable;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Helper class for updating the statement_usage table.
 * This is used internally by SqlUsageTracker.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 * @internal
 */
class StatementUsageTable {

	const DEFAULT_TABLE_NAME = 'wbc_statement_usage';

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var Database
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
	 * @param Database $connection
	 * @param int $batchSize defaults to 100
	 * @param string|null $tableName defaults to wbc_statement_usage
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdParser $idParser,
		Database $connection,
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
	 * @param StatementUsage[] $usages
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
	 * @param StatementUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[]
	 */
	private function makeUsageRows( $pageId, array $usages ) {
		$rows = array();

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof StatementUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain StatementUsage objects.' );
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
	 * @param StatementUsage[] $usages
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

		$this->connection->startAtomic( __METHOD__ );

		foreach ( $batches as $rows ) {
			$this->connection->insert( $this->tableName, $rows, __METHOD__, array( 'IGNORE' ) );
			$c += $this->connection->affectedRows();
		}

		$this->connection->endAtomic( __METHOD__ );

		return $c;
	}

	/**
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 * @return StatementUsage[] StatementUsage identity string => StatementUsage
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
	 * @return StatementUsage[]
	 */
	private function convertRowsToUsages( Traversable $rows ) {
		$usages = array();

		foreach ( $rows as $object ) {
			$entityId = $this->idParser->parse( $object->su_entity_id );
			$propertyId = $this->idParser->parse( $object->su_property_id );
			$statementExists = (bool)$object->su_statement_exists;
			$usage = new StatementUsage( $entityId, $propertyId, $statementExists );
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

		$this->connection->startAtomic( __METHOD__ );

		foreach ( $usages as $usage ) {
			$usageConditions[] = $db->makeList( array(
				'su_entity_id' => $usage->getEntityId()->getSerialization(),
				'su_property_id' => $usage->getpropertyId()->getSerialization(),
				'su_statement_exists' => $usage->getStatementExists(),
			), LIST_AND );
		}

		// Collect affected row IDs, so we can use them for an
		// efficient update query on the master db.
		$where = array(
			'su_page_id' => (int)$pageId,
			$db->makeList( $usageConditions, LIST_OR )
		);
		//TODO: need to chunk this
		// foreach ( $rowIdChunks as $chunk ) {
			$this->connection->delete(
				$this->tableName,
				$where,
				__METHOD__
			);
		// }

		$this->connection->endAtomic( __METHOD__ );
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
		// if ( empty( $entityIds ) ) {
			return new ArrayIterator();
		// }
		//TODO: DECIDE HOW THIS FUNCTION SHOULD WORK
		
	}

	/**
	 * @param Traversable $rows
	 *
	 * @return PageEntityUsages[]
	 */
	private function foldRowsIntoPageEntityUsages( Traversable $rows ) {
		$usagesPerPage = array();

		foreach ( $rows as $row ) {
			$pageId = (int)$row->su_page_id;

			if ( isset( $usagesPerPage[$pageId] ) ) {
				$pageEntityDataUsages = $usagesPerPage[$pageId];
			} else {
				$pageEntityDataUsages = new PageEntityDataUsages( $pageId );
			}

			$entityId = $this->idParser->parse( $row->su_entity_id );
			$propertyId = $this->idParser->parse( $row->su_property_id );
			$statementExists = (bool)$row->su_statement_exists;
			$usage = new StatementUsage( $entityId, $propertyId, $statementExists );
			$pageEntityDataUsages->addUsages( array( $usage ) );
			$usagesPerPage[$pageId] = $pageEntityDataUsages;
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
		// Note: We need to use one (sub)query per entity here, per T116404
		$subQueries = $this->getUsedEntityIdStringsQueries( $idStrings );

		$values = [];
		if ( $this->connection->getType() === 'mysql' ) {
			// On MySQL we can UNION all queries and run them at once
			$sql = $this->connection->unionQueries( $subQueries, true );

			$res = $this->connection->query( $sql, __METHOD__ );
			foreach ( $res as $row ) {
				$values[] = $row->su_entity_id;
			}
		} else {
			foreach ( $subQueries as $sql ) {
				$res = $this->connection->query( $sql, __METHOD__ );
				if ( $res->numRows() ) {
					$values[] = $res->current()->su_entity_id;
				}
			}
		}

		return $values;
	}

	/**
	 * @param string[] $idStrings
	 *
	 * @return string[]
	 */
	private function getUsedEntityIdStringsQueries( array $idStrings ) {
		$subQueries = [];

		foreach ( $idStrings as $idString ) {
			$subQueries[] = $this->connection->selectSQLText(
				$this->tableName,
				'su_entity_id',
				[ 'su_entity_id' => $idString ],
				'',
				[ 'LIMIT' => 1 ]
			);
		}

		return $subQueries;
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
		//TODO: REPLACE eu_row_id with the three columns that make up our primary key?
		$rowIds = $this->connection->selectFieldValues(
			$this->tableName,
			'eu_row_id',
			$where,
			$method
		);

		return array_map( 'intval', $rowIds ?: array() );
	}

}
